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
 * Silence taxes the friendly spells a dominion casts, so a realm's support
 * mage can be made expensive to keep running during an assault.
 */
class SilenceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var SpellCalculator */
    protected $spellCalculator;

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
        $this->spellCalculator = $this->app->make(SpellCalculator::class);

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

    protected function silence(): void
    {
        DominionSpell::create([
            'dominion_id' => $this->target->id,
            'spell_id' => Spell::where('key', 'silence')->firstOrFail()->id,
            'duration' => 4,
            'cast_by_dominion_id' => $this->dominion->id,
        ]);

        $this->target->unsetRelation('spells');
    }

    public function testSilenceRequiresAWar(): void
    {
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You cannot cast Silence outside of war');

        $this->spellActionService->castSpell($this->dominion, 'silence', $this->target);
    }

    public function testSilenceLastsFourHours(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'silence', $this->target);

        $silence = Spell::where('key', 'silence')->firstOrFail();
        $activeSpell = DominionSpell::where('dominion_id', $this->target->id)
            ->where('spell_id', $silence->id)
            ->firstOrFail();

        // Base 4 hours, extended by 2 during a one-sided war
        $this->assertEquals(6, $activeSpell->duration);
    }

    public function testSilenceHasACooldown(): void
    {
        $this->declareWar();

        $this->spellActionService->castSpell($this->dominion, 'silence', $this->target);

        // The cooldown is read from the caster's recent casts
        $this->dominion->unsetRelation('recentSpellCasts');
        $this->target->unsetRelation('spells');

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You can only cast Silence every 6 hours');

        $this->spellActionService->castSpell($this->dominion, 'silence', $this->target);
    }

    public function testSilenceRaisesFriendlySpellCosts(): void
    {
        $arcaneWard = Spell::where('key', 'arcane_ward')->firstOrFail();
        $costBefore = $this->spellCalculator->getManaCost($this->target, $arcaneWard);

        $this->silence();

        $this->assertEquals(
            (int)round($costBefore * 1.25),
            $this->spellCalculator->getManaCost($this->target, $arcaneWard)
        );
    }

    public function testSilenceLeavesOtherSpellsAlone(): void
    {
        $aresCall = Spell::where('key', 'ares_call')->firstOrFail();
        $fireball = Spell::where('key', 'fireball')->firstOrFail();
        $selfCostBefore = $this->spellCalculator->getManaCost($this->target, $aresCall);
        $warCostBefore = $this->spellCalculator->getManaCost($this->target, $fireball);

        $this->silence();

        $this->assertEquals($selfCostBefore, $this->spellCalculator->getManaCost($this->target, $aresCall));
        $this->assertEquals($warCostBefore, $this->spellCalculator->getManaCost($this->target, $fireball));
    }

    /**
     * The tax follows the caster, not the realm: a silenced mage pays more
     * wherever they cast, and their realmmates do not.
     */
    public function testSilenceOnlyTaxesTheSilencedDominion(): void
    {
        $arcaneWard = Spell::where('key', 'arcane_ward')->firstOrFail();
        $costBefore = $this->spellCalculator->getManaCost($this->dominion, $arcaneWard);

        $this->silence();

        $this->assertEquals($costBefore, $this->spellCalculator->getManaCost($this->dominion, $arcaneWard));
    }
}
