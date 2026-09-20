<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Magic Ward halves incoming war spell damage for 24 hours. It leaves the
 * dominion Fractured when it ends, which blocks a recast for 6 hours. Break
 * Ward strips 2 hours from it and can end it early.
 */
class MagicWardTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Dark Elf')->firstOrFail());
        $this->dominion->land_plain = 8000;
        $this->dominion->resource_mana = 100000;
        $this->dominion->military_wizards = 5000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->target->land_plain = 8000;
        $this->target->resource_mana = 100000;

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function activateMagicWard(Dominion $dominion, int $duration = 24): DominionSpell
    {
        $spell = Spell::where('key', 'magic_ward')->firstOrFail();

        $activeSpell = DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => $spell->id,
            'duration' => $duration,
            'cast_by_dominion_id' => $dominion->id,
        ]);

        $dominion->unsetRelation('spells');

        return $activeSpell;
    }

    protected function declareWar(): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);
    }

    public function testMagicWardLastsTwentyFourHours(): void
    {
        $this->spellActionService->castSpell($this->dominion, 'magic_ward');

        $activeSpell = DominionSpell::where('dominion_id', $this->dominion->id)->firstOrFail();

        $this->assertEquals(24, $activeSpell->duration);
    }

    public function testMagicWardHalvesWarSpellDamage(): void
    {
        $opsCalculator = $this->app->make(OpsCalculator::class);

        $unwarded = $opsCalculator->getSpellDamageMultiplier($this->target, 'lightning_bolt', $this->dominion);
        $this->activateMagicWard($this->target);
        $warded = $opsCalculator->getSpellDamageMultiplier($this->target, 'lightning_bolt', $this->dominion);

        $this->assertEquals(1.0, $unwarded);
        $this->assertEquals(0.5, $warded);
    }

    public function testMagicWardCannotBeRecastWhileFractured(): void
    {
        $fractured = Spell::where('key', 'fractured')->firstOrFail();
        DominionSpell::create([
            'dominion_id' => $this->dominion->id,
            'spell_id' => $fractured->id,
            'duration' => 6,
            'cast_by_dominion_id' => $this->dominion->id,
        ]);
        $this->dominion->unsetRelation('spells');

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('Your dominion is Fractured and cannot cast Magic Ward for another 6 hours');

        $this->spellActionService->castSpell($this->dominion, 'magic_ward');
    }

    /**
     * Only a ward that was torn down leaves the dominion Fractured. One that
     * runs its full 24 hours simply ends.
     */
    public function testMagicWardDoesNotFractureWhenItExpires(): void
    {
        $this->dominion->protection_ticks_remaining = 0;
        $this->activateMagicWard($this->dominion, 1);
        $this->dominion->save();

        $this->app->make(TickService::class)->performTick($this->round);

        $this->assertEquals(
            0,
            DominionSpell::where('dominion_id', $this->dominion->id)->count(),
            'An expired ward should leave nothing behind'
        );
    }

    public function testMagicWardCanBeRecastAfterItExpires(): void
    {
        $this->dominion->protection_ticks_remaining = 0;
        $this->activateMagicWard($this->dominion, 1);
        $this->dominion->save();

        $this->app->make(TickService::class)->performTick($this->round);
        $this->dominion->refresh();
        $this->dominion->resource_mana = 100000;
        $this->dominion->unsetRelation('spells');

        $this->spellActionService->castSpell($this->dominion, 'magic_ward');

        $this->assertEquals(
            24,
            DominionSpell::where('dominion_id', $this->dominion->id)->firstOrFail()->duration
        );
    }

    public function testBreakWardRemovesTwoHours(): void
    {
        $this->declareWar();
        $this->activateMagicWard($this->target);

        $result = $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);

        $activeSpell = DominionSpell::where('dominion_id', $this->target->id)->firstOrFail();
        $this->assertEquals('magic_ward', $activeSpell->spell->key);
        $this->assertEquals(22, $activeSpell->duration);
        $this->assertStringContainsString('2 hours of Magic Ward', $result['message']);
    }

    public function testBreakWardFracturesTheTargetWhenTheWardRunsOut(): void
    {
        $this->declareWar();
        $this->activateMagicWard($this->target, 2);

        $result = $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);

        $activeSpell = DominionSpell::where('dominion_id', $this->target->id)->firstOrFail();
        $this->assertEquals('fractured', $activeSpell->spell->key);
        $this->assertEquals(6, $activeSpell->duration);
        $this->assertStringContainsString('You inflicted Fractured', $result['message']);
    }

    /**
     * The ward does not defend itself: Break Ward always strips the full 2 hours.
     */
    public function testBreakWardIgnoresTheWardsDamageReduction(): void
    {
        $this->declareWar();
        $this->activateMagicWard($this->target, 10);

        $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);

        $this->assertEquals(8, DominionSpell::where('dominion_id', $this->target->id)->firstOrFail()->duration);
    }

    public function testBreakWardEarnsWizardMastery(): void
    {
        $this->declareWar();
        $this->activateMagicWard($this->target);
        $masteryBefore = $this->dominion->wizard_mastery;

        $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);

        $this->assertGreaterThan($masteryBefore, $this->dominion->wizard_mastery);
    }

    public function testBreakWardIsRejectedWithoutAWard(): void
    {
        $this->declareWar();
        $manaBefore = $this->dominion->resource_mana;

        try {
            $this->spellActionService->castSpell($this->dominion, 'break_ward', $this->target);
            $this->fail('Break Ward should be rejected when the target has no Magic Ward');
        } catch (GameException $e) {
            $this->assertStringContainsString('is not protected by Magic Ward', $e->getMessage());
        }

        $this->assertEquals($manaBefore, $this->dominion->resource_mana, 'A rejected cast should not cost mana');
    }
}
