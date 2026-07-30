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
