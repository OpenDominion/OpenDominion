<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\DominionSpell;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Spell;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * The Grand Magister is the realm's support caster: friendly spells carry no
 * cooldown for them, so their wizard strength is the only limit on how much
 * mending or empowering they do. Their own self spells are unaffected.
 */
class GrandMagisterTest extends AbstractBrowserKitTestCase
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
    protected $realmmate;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-4 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());
        $this->dominion->land_plain = 8000;
        $this->dominion->resource_mana = 200000;

        $this->realmmate = $this->createDominionWithLegacyStats(
            $this->createUser(null, ['email' => uniqid('realmmate', true) . '@example.com']),
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->dominion->realm
        );
        $this->realmmate->land_plain = 8000;
        $this->realmmate->save();

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->spellCalculator = $this->app->make(SpellCalculator::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    protected function appointGrandMagister(Dominion $dominion): void
    {
        $dominion->realm->magister_dominion_id = $dominion->id;
        $dominion->realm->save();
        $dominion->unsetRelation('realm');
    }

    public function testFriendlySpellsNormallyHaveACooldown(): void
    {
        $this->spellActionService->castSpell($this->dominion, 'illumination', $this->realmmate);
        $this->dominion->unsetRelation('recentSpellCasts');

        $this->assertEquals(
            3,
            $this->spellCalculator->getSpellCooldown($this->dominion, Spell::where('key', 'illumination')->firstOrFail())
        );
    }

    public function testTheGrandMagisterHasNoFriendlyCooldown(): void
    {
        $this->appointGrandMagister($this->dominion);

        $this->spellActionService->castSpell($this->dominion, 'illumination', $this->realmmate);
        $this->dominion->unsetRelation('recentSpellCasts');

        $this->assertEquals(
            0,
            $this->spellCalculator->getSpellCooldown($this->dominion, Spell::where('key', 'illumination')->firstOrFail())
        );
    }

    /**
     * Once the champion has spent the charge, the Grand Magister can hand them
     * another immediately. Anyone else waits out the cooldown.
     */
    public function testTheGrandMagisterCanKeepArcaneConduitRunning(): void
    {
        $this->appointGrandMagister($this->dominion);

        $this->spellActionService->castSpell($this->dominion, 'arcane_conduit', $this->realmmate);
        $this->spendTheCharge();

        $this->spellActionService->castSpell($this->dominion, 'arcane_conduit', $this->realmmate);

        $this->assertEquals(2, $this->dominion->stat_spell_success);
    }

    public function testAnyoneElseWaitsToHandOverAnotherArcaneConduit(): void
    {
        $this->spellActionService->castSpell($this->dominion, 'arcane_conduit', $this->realmmate);
        $this->spendTheCharge();

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('every 3 hours');

        $this->spellActionService->castSpell($this->dominion, 'arcane_conduit', $this->realmmate);
    }

    /**
     * Stands in for the champion landing the spell the charge was holding.
     */
    protected function spendTheCharge(): void
    {
        DominionSpell::where('dominion_id', $this->realmmate->id)
            ->where('spell_id', Spell::where('key', 'arcane_conduit')->firstOrFail()->id)
            ->delete();

        $this->dominion->unsetRelation('recentSpellCasts');
        $this->realmmate->unsetRelation('spells');
    }

    public function testTheGrandMagisterStillWaitsOnSelfSpells(): void
    {
        $this->appointGrandMagister($this->dominion);
        $this->dominion->protection_ticks_remaining = 0;

        $this->spellActionService->castSpell($this->dominion, 'fools_gold');
        $this->dominion->unsetRelation('recentSpellCasts');

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('every 20 hours');

        $this->spellActionService->castSpell($this->dominion, 'fools_gold');
    }
}
