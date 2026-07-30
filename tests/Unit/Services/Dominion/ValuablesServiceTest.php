<?php

namespace OpenDominion\Tests\Unit\Services\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Helpers\ValuablesHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\Valuable;
use OpenDominion\Models\ValuablesTracking;
use OpenDominion\Services\Dominion\Actions\ValuablesActionService;
use OpenDominion\Services\Dominion\ValuablesService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Covers the discovery roll in isolation.
 *
 * The ratio check that gates this lives in EspionageActionService, and
 * random_chance() is mocked by a single global, so driving the service directly
 * is the only way to reach the "infiltrated but found nothing" outcome without
 * the ratio roll forcing the same result.
 */
class ValuablesServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var ValuablesService */
    protected $valuablesService;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

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

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Halfling')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->save();

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Nomad')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->save();

        $this->valuablesService = app(ValuablesService::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testAttemptDiscovery_FoundNothing_IsStillASuccessAndAdvancesProgress()
    {
        $result = $this->valuablesService->attemptDiscovery($this->dominion, $this->target);

        // A whiffed search is deliberately not a failure - only the ratio check
        // in EspionageActionService can fail the operation outright.
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('find nothing of value', $result['message']);
        $this->assertEquals(0, Valuable::count());

        $this->assertEquals(
            ValuablesHelper::SPY_OP_PROGRESS_INCREMENT,
            $this->tracking()->progress
        );
    }

    public function testAttemptDiscovery_Found_CreatesValuableAndResetsProgress()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->seedTracking(40);

        $result = $this->valuablesService->attemptDiscovery($this->dominion, $this->target);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, Valuable::count());

        $valuable = Valuable::first();
        $this->assertEquals($this->dominion->id, $valuable->source_dominion_id);
        $this->assertEquals($this->target->id, $valuable->target_dominion_id);
        $this->assertEquals(Valuable::STATUS_DISCOVERED, $valuable->status);

        $tracking = $this->tracking();
        $this->assertEquals(0, $tracking->progress);
        $this->assertNotNull($tracking->last_discovered_at);
    }

    public function testAttemptDiscovery_OnCooldown_FailsWithoutTouchingProgress()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->seedTracking(30, now());

        $result = $this->valuablesService->attemptDiscovery($this->dominion, $this->target);

        $this->assertFalse($result['success']);
        $this->assertEquals(0, Valuable::count());
        $this->assertEquals(30, $this->tracking()->progress);
    }

    public function testAttemptPassiveDiscovery_StillWorks()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $message = $this->valuablesService->attemptPassiveDiscovery($this->dominion, $this->target);

        $this->assertNotEmpty($message);
        $this->assertEquals(1, Valuable::count());
    }

    #[DataProvider('rarityBandProvider')]
    public function testSelectRarity_UsesFiveEvenBands(float $spa, string $expected)
    {
        $land = $this->landCalculator->getTotalLand($this->target);

        // Below the 350 acre offset landScore floors at 0, so score == spa / 2
        // and these cases isolate the banding from the land term.
        $this->assertLessThanOrEqual(350, $land, 'Fixture assumption: target is at or below the land offset');

        $this->target->military_spies = (int) round($spa * $land);
        $this->target->military_assassins = 0;

        $this->assertEqualsWithDelta($spa, $this->militaryCalculator->getSpyRatio($this->target, 'defense'), 0.01);

        $this->assertEquals(
            $expected,
            app(ValuablesHelper::class)->selectRarity($this->dominion, $this->target)
        );
    }

    public static function rarityBandProvider(): array
    {
        // score = spa / 2, banded by floor(score * 5)
        return [
            'no spy defense'   => [0.0, ValuablesHelper::RARITY_COMMON],
            'spa 0.25'         => [0.25, ValuablesHelper::RARITY_COMMON],   // was uncommon under round($score * 4)
            'spa 0.375'        => [0.375, ValuablesHelper::RARITY_COMMON],
            'spa 0.5'          => [0.5, ValuablesHelper::RARITY_UNCOMMON],
            'spa 0.75'         => [0.75, ValuablesHelper::RARITY_UNCOMMON], // was rare under round($score * 4)
            'spa 1.0'          => [1.0, ValuablesHelper::RARITY_RARE],
            'spa above cap'    => [2.0, ValuablesHelper::RARITY_RARE],      // spyScore clamps at 1.0
        ];
    }

    #[DataProvider('minimumSpyHoursProvider')]
    public function testGetMinimumRequiredSpyHours_ScalesByLandAndRarity(string $rarity, float $multiplier)
    {
        $this->dominion->land_plain = 1000;
        $this->dominion->save();

        $land = $this->landCalculator->getTotalLand($this->dominion);

        $this->assertEquals(
            (int) ceil($land * 0.75 * $multiplier),
            app(ValuablesHelper::class)->getMinimumRequiredSpyHours($this->dominion, $rarity)
        );
    }

    public static function minimumSpyHoursProvider(): array
    {
        // Multipliers mirror getRarityConfig()'s spy_hours_multiplier
        return [
            'common'    => [ValuablesHelper::RARITY_COMMON, 0.5],
            'uncommon'  => [ValuablesHelper::RARITY_UNCOMMON, 1.0],
            'rare'      => [ValuablesHelper::RARITY_RARE, 2.0],
            'epic'      => [ValuablesHelper::RARITY_EPIC, 3.0],
            'legendary' => [ValuablesHelper::RARITY_LEGENDARY, 5.0],
        ];
    }

    public function testGetMinimumRequiredSpyHours_RarerValuablesFloorHigher()
    {
        $helper = app(ValuablesHelper::class);

        $this->assertGreaterThan(
            $helper->getMinimumRequiredSpyHours($this->dominion, ValuablesHelper::RARITY_COMMON),
            $helper->getMinimumRequiredSpyHours($this->dominion, ValuablesHelper::RARITY_LEGENDARY)
        );
    }

    public function testGetEffectiveRequiredSpyHours_FloorsSmallHeistsButLeavesLargeOnesAlone()
    {
        $helper = app(ValuablesHelper::class);
        $minimum = $helper->getMinimumRequiredSpyHours($this->dominion, ValuablesHelper::RARITY_COMMON);

        $cheap = $this->makeValuable(1);
        $this->assertEquals($minimum, $helper->getEffectiveRequiredSpyHours($cheap, $this->dominion));

        $expensive = $this->makeValuable($minimum * 10);
        $this->assertEquals($minimum * 10, $helper->getEffectiveRequiredSpyHours($expensive, $this->dominion));

        // Display-safe: the stored value is never mutated by the lookup
        $this->assertEquals(1, $cheap->fresh()->required_spy_hours);
    }

    public function testGetEffectiveRequiredSpyHours_UsesTheValuablesOwnRarity()
    {
        $helper = app(ValuablesHelper::class);

        $common = $this->makeValuable(1);
        $legendary = $this->makeValuable(1, ['rarity' => ValuablesHelper::RARITY_LEGENDARY]);

        $this->assertGreaterThan(
            $helper->getEffectiveRequiredSpyHours($common, $this->dominion),
            $helper->getEffectiveRequiredSpyHours($legendary, $this->dominion)
        );
    }

    public function testDiscoveryAppliesTheMinimumForTheDiscoverer()
    {
        global $mockRandomChance;
        $mockRandomChance = true;

        $this->valuablesService->attemptDiscovery($this->dominion, $this->target);

        $valuable = Valuable::first();
        $minimum = app(ValuablesHelper::class)->getMinimumRequiredSpyHours($this->dominion, $valuable->rarity);

        $this->assertGreaterThanOrEqual($minimum, $valuable->required_spy_hours);
    }

    public function testPurchaseRaisesRequiredSpyHoursToTheBuyersMinimum()
    {
        // Buyer is 10x the seller, so the seller's cheap intel must be re-floored
        $buyerUser = $this->createUser();
        $buyer = $this->createDominionWithLegacyStats($buyerUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $buyer->realm_id = $this->dominion->realm_id;
        $buyer->land_plain = 3000;
        $buyer->resource_platinum = 10000000;
        $buyer->save();

        $valuable = $this->makeValuable(50, ['is_listed' => true]);

        app(ValuablesActionService::class)->purchaseValuable($buyer, $valuable);

        $expected = app(ValuablesHelper::class)->getMinimumRequiredSpyHours($buyer, $valuable->rarity);

        $valuable->refresh();
        $this->assertEquals($buyer->id, $valuable->source_dominion_id);
        $this->assertEquals($expected, $valuable->required_spy_hours);
        $this->assertGreaterThan(50, $valuable->required_spy_hours);
    }

    public function testPurchaseDoesNotLowerRequiredSpyHoursForASmallBuyer()
    {
        $buyerUser = $this->createUser();
        $buyer = $this->createDominionWithLegacyStats($buyerUser, $this->round, Race::where('name', 'Human')->firstOrFail());
        $buyer->realm_id = $this->dominion->realm_id;
        $buyer->resource_platinum = 10000000;
        $buyer->save();

        $valuable = $this->makeValuable(99999, ['is_listed' => true]);

        app(ValuablesActionService::class)->purchaseValuable($buyer, $valuable);

        $this->assertEquals(99999, $valuable->refresh()->required_spy_hours);
    }

    public function testSalePrice_HoldsAtFullPriceThroughTheGracePeriod()
    {
        $helper = app(ValuablesHelper::class);
        $max = ValuablesHelper::getRarityConfig()[ValuablesHelper::RARITY_COMMON]['base_value_max'];

        foreach ([0, 1, ValuablesHelper::SALE_PRICE_GRACE_HOURS] as $hoursAgo) {
            $valuable = $this->makeStolenValuable($hoursAgo);

            $this->assertEquals(
                $max,
                $helper->getCurrentSalePrice($valuable),
                "Expected full price {$hoursAgo}h after the heist"
            );
        }
    }

    public function testSalePrice_DecaysOnlyAfterTheGracePeriod()
    {
        $helper = app(ValuablesHelper::class);
        $config = ValuablesHelper::getRarityConfig()[ValuablesHelper::RARITY_COMMON];

        $justAfterGrace = $this->makeStolenValuable(ValuablesHelper::SALE_PRICE_GRACE_HOURS + 1);
        $this->assertLessThan($config['base_value_max'], $helper->getCurrentSalePrice($justAfterGrace));

        // Decay still spans EXPIRATION_HOURS, so the floor lands grace + 48h out
        $atFloor = $this->makeStolenValuable(ValuablesHelper::SALE_PRICE_GRACE_HOURS + ValuablesHelper::EXPIRATION_HOURS);
        $this->assertEquals($config['base_value_min'], $helper->getCurrentSalePrice($atFloor));

        $wellPastFloor = $this->makeStolenValuable(500);
        $this->assertEquals($config['base_value_min'], $helper->getCurrentSalePrice($wellPastFloor));
    }

    public function testRemainingSalePriceGraceHours_CountsDownThenZeroes()
    {
        $helper = app(ValuablesHelper::class);

        $this->assertEquals(
            ValuablesHelper::SALE_PRICE_GRACE_HOURS,
            $helper->getRemainingSalePriceGraceHours($this->makeStolenValuable(0))
        );

        $this->assertEquals(2, $helper->getRemainingSalePriceGraceHours(
            $this->makeStolenValuable(ValuablesHelper::SALE_PRICE_GRACE_HOURS - 2)
        ));

        $this->assertEquals(0, $helper->getRemainingSalePriceGraceHours(
            $this->makeStolenValuable(ValuablesHelper::SALE_PRICE_GRACE_HOURS + 1)
        ));
    }

    protected function makeStolenValuable(int $stolenHoursAgo): Valuable
    {
        return $this->makeValuable(100, [
            'status' => Valuable::STATUS_STOLEN,
            'stolen_at' => now()->subHours($stolenHoursAgo),
        ]);
    }

    protected function makeValuable(int $requiredSpyHours, array $attributes = []): Valuable
    {
        $valuable = new Valuable();
        $valuable->round_id = $this->round->id;
        $valuable->source_dominion_id = $this->dominion->id;
        $valuable->target_dominion_id = $this->target->id;
        $valuable->name = 'Test Valuable';
        $valuable->rarity = ValuablesHelper::RARITY_COMMON;
        $valuable->type = 'relic';
        $valuable->status = Valuable::STATUS_DISCOVERED;
        $valuable->required_spy_hours = $requiredSpyHours;
        $valuable->discovered_at = now();
        $valuable->forceFill($attributes);
        $valuable->save();

        return $valuable;
    }

    protected function seedTracking(int $progress, $lastDiscoveredAt = null): ValuablesTracking
    {
        return ValuablesTracking::create([
            'round_id' => $this->round->id,
            'source_dominion_id' => $this->dominion->id,
            'target_dominion_id' => $this->target->id,
            'progress' => $progress,
            'last_discovered_at' => $lastDiscoveredAt,
        ]);
    }

    protected function tracking(): ValuablesTracking
    {
        return ValuablesTracking::query()
            ->where('source_dominion_id', $this->dominion->id)
            ->where('target_dominion_id', $this->target->id)
            ->firstOrFail();
    }
}
