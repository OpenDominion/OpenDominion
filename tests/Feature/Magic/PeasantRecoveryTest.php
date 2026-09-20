<?php

namespace OpenDominion\Tests\Feature\Magic;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
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
 * Fireball kills a share of the peasants a dominion actually has, with nothing
 * shielding them. Those deaths are recorded until they grow back, and the two
 * new spells turn that record into a recovery: Resurrection holds a floor each
 * hour, Revive Peasants brings a share of them back at once.
 */
class PeasantRecoveryTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var SpellActionService */
    protected $spellActionService;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    /** @var Dominion Realmmate of the target, who does the reviving */
    protected $courtMage;

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

        $mageUser = $this->createUser();
        $this->courtMage = $this->createDominionWithLegacyStats(
            $mageUser,
            $this->round,
            Race::where('name', 'Human')->firstOrFail(),
            $this->target->realm
        );
        $this->courtMage->land_plain = 8000;
        $this->courtMage->resource_mana = 100000;
        $this->courtMage->save();

        $this->target->realm->magister_dominion_id = $this->courtMage->id;
        $this->target->realm->save();

        $this->spellActionService = $this->app->make(SpellActionService::class);
        $this->populationCalculator = $this->app->make(PopulationCalculator::class);

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

    protected function activateSpell(Dominion $dominion, string $key, int $duration = 12): void
    {
        DominionSpell::create([
            'dominion_id' => $dominion->id,
            'spell_id' => Spell::where('key', $key)->firstOrFail()->id,
            'duration' => $duration,
            'cast_by_dominion_id' => $dominion->id,
        ]);

        $dominion->unsetRelation('spells');
    }

    public function testFireballKillsAShareOfCurrentPeasants(): void
    {
        $this->declareWar();
        $this->target->peasants = 40000;

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(38000, $this->target->peasants);
        $this->assertEquals(2000, $this->target->peasants_killed);
    }

    /**
     * The old 50% floor and 5-per-wizard protection are gone, so two dominions
     * with the same peasants take the same damage.
     */
    public function testWizardsNoLongerShieldPeasants(): void
    {
        $this->declareWar();
        $this->target->peasants = 40000;
        $this->target->military_wizards = 10000;

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $this->assertEquals(38000, $this->target->peasants);
    }

    public function testFireballNoLongerAppliesBurning(): void
    {
        $this->declareWar();
        $this->target->peasants = 40000;

        $this->spellActionService->castSpell($this->dominion, 'fireball', $this->target);

        $burning = Spell::where('key', 'burning')->firstOrFail();

        $this->assertEquals(
            0,
            DominionSpell::where('dominion_id', $this->target->id)->where('spell_id', $burning->id)->count()
        );
        $this->assertEquals(0, $this->target->fireball_meter);
    }

    public function testResurrectionRestoresPeasantsTowardsTheWizardRatio(): void
    {
        $this->target->protection_ticks_remaining = 0;
        $this->target->peasants = 1000;
        // 0.5 raw wizards per acre holds the dominion at half of maximum population
        $this->target->military_wizards = (int)round(0.5 * $this->app->make(LandCalculator::class)->getTotalLand($this->target));
        $this->activateSpell($this->target, 'resurrection');
        $this->target->save();

        $maxPeasants = $this->populationCalculator->getMaxPeasantPopulation($this->target);

        $this->app->make(TickService::class)->performTick($this->round);
        $this->target->refresh();

        $this->assertGreaterThanOrEqual((int)floor($maxPeasants * 0.5), $this->target->peasants);
    }

    public function testResurrectionIsCappedBelowMaximumPopulation(): void
    {
        $this->target->protection_ticks_remaining = 0;
        $this->target->peasants = 1000;
        // Far more wizards than the 75% ceiling calls for
        $this->target->military_wizards = 20000;
        $this->activateSpell($this->target, 'resurrection');
        $this->target->save();

        $maxPeasants = $this->populationCalculator->getMaxPeasantPopulation($this->target);

        $this->app->make(TickService::class)->performTick($this->round);
        $this->target->refresh();

        $this->assertLessThan($maxPeasants, $this->target->peasants);
        $this->assertGreaterThanOrEqual((int)floor($maxPeasants * 0.75), $this->target->peasants);
    }

    /**
     * Peasants that grow back on their own are no longer owed, so a realm
     * cannot bank deaths and revive them later.
     */
    public function testNaturalGrowthPaysDownTheRecord(): void
    {
        $this->target->protection_ticks_remaining = 0;
        $this->target->peasants = 20000;
        $this->target->peasants_killed = 5000;
        $this->target->save();

        $this->app->make(TickService::class)->performTick($this->round);
        $this->target->refresh();

        $grown = $this->target->peasants_last_hour;

        $this->assertGreaterThan(0, $grown, 'The target should have grown peasants this tick');
        $this->assertEquals(5000 - $grown, $this->target->peasants_killed);
    }

    public function testRevivePeasantsBringsBackAShareOfTheRecord(): void
    {
        $this->target->peasants = 30000;
        $this->target->peasants_killed = 4000;
        $this->target->save();

        $result = $this->spellActionService->castSpell($this->courtMage, 'revive_peasants', $this->target);

        $this->assertEquals(30200, $this->target->peasants);
        $this->assertEquals(3800, $this->target->peasants_killed);
        $this->assertStringContainsString('200', $result['message']);
    }

    public function testRevivePeasantsCannotPushPastMaximumPopulation(): void
    {
        $maxPeasants = $this->populationCalculator->getMaxPeasantPopulation($this->target);
        $this->target->peasants = $maxPeasants;
        $this->target->peasants_killed = 4000;
        $this->target->save();

        $this->spellActionService->castSpell($this->courtMage, 'revive_peasants', $this->target);

        $this->assertEquals($maxPeasants, $this->target->peasants);
        $this->assertEquals(4000, $this->target->peasants_killed);
    }

    public function testRevivePeasantsIsRejectedWithNoDeaths(): void
    {
        $manaBefore = $this->courtMage->resource_mana;

        try {
            $this->spellActionService->castSpell($this->courtMage, 'revive_peasants', $this->target);
            $this->fail('Revive Peasants should be rejected when nothing has been killed');
        } catch (GameException $e) {
            $this->assertStringContainsString('no fallen peasants', $e->getMessage());
        }

        $this->assertEquals($manaBefore, $this->courtMage->resource_mana, 'A rejected cast should not cost mana');
    }
}
