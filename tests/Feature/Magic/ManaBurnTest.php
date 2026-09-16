<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * Mana Burn destroys a share of the target's mana outright, taking more than
 * Steal Mana can carry off and leaving nothing behind for the caster.
 */
class ManaBurnTest extends AbstractBrowserKitTestCase
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
        $this->target->resource_mana = 200000;

        $this->spellActionService = $this->app->make(SpellActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    protected function declareWar(): void
    {
        RealmWar::create([
            'source_realm_id' => $this->dominion->realm_id,
            'target_realm_id' => $this->target->realm_id,
        ]);
    }

    public function testManaBurnRequiresAWar(): void
    {
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot cast Mana Burn outside of war');

        $this->spellActionService->castSpell($this->dominion, 'mana_burn', $this->target);
    }

    public function testManaBurnDestroysFivePercentOfMana(): void
    {
        $this->declareWar();

        $result = $this->spellActionService->castSpell($this->dominion, 'mana_burn', $this->target);

        $this->assertEquals(190000, $this->target->resource_mana);
        $this->assertStringContainsString('10,000', $result['message']);
    }

    /**
     * Burned mana is destroyed, not taken. The caster only pays for the cast.
     */
    public function testManaBurnDoesNotGiveTheCasterMana(): void
    {
        $this->declareWar();

        $spell = Spell::where('key', 'mana_burn')->firstOrFail();
        $manaCost = $this->app->make(SpellCalculator::class)->getManaCost($this->dominion, $spell);

        $this->spellActionService->castSpell($this->dominion, 'mana_burn', $this->target);

        $this->assertEquals(100000 - $manaCost, $this->dominion->resource_mana);
    }

    public function testMagicWardHalvesManaBurn(): void
    {
        $this->declareWar();

        $magicWard = Spell::where('key', 'magic_ward')->firstOrFail();
        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id' => $magicWard->id,
            'duration' => 24,
            'cast_by_dominion_id' => $this->target->id,
        ]);
        $this->target->unsetRelation('spells');

        $this->spellActionService->castSpell($this->dominion, 'mana_burn', $this->target);

        $this->assertEquals(195000, $this->target->resource_mana);
    }

    public function testManaBurnEarnsWizardMastery(): void
    {
        $this->declareWar();
        $masteryBefore = $this->dominion->wizard_mastery;

        $this->spellActionService->castSpell($this->dominion, 'mana_burn', $this->target);

        $this->assertGreaterThan($masteryBefore, $this->dominion->wizard_mastery);
    }
}
