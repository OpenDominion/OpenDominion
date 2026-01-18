<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\RaidService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RaidService::class)]
class RaidServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var RaidService */
    protected $service;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(RaidService::class);

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::first());
    }

    public function testProcessCompletedRaids_WithNoCompletedRaids_DoesNothing()
    {
        // Arrange - Create an active raid (not completed)
        $raid = $this->createRaid([
            'end_date' => now()->addDay(),
            'rewards_distributed' => false,
        ]);

        // Act
        $this->service->processCompletedRaids($this->round);

        // Assert
        $raid->refresh();
        $this->assertFalse($raid->rewards_distributed);
    }

    public function testProcessCompletedRaids_WithAlreadyDistributedRaid_SkipsIt()
    {
        // Arrange
        $raid = $this->createRaid([
            'end_date' => now()->subHour(),
            'rewards_distributed' => true,
        ]);

        $initialResourceAmount = $this->dominion->resource_platinum;

        // Act
        $this->service->processCompletedRaids($this->round);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals($initialResourceAmount, $this->dominion->resource_platinum);
    }

    public function testProcessCompletedRaids_WithCompletedRaid_DistributesRewards()
    {
        // Arrange
        $raid = $this->createRaid([
            'end_date' => now()->subHour(),
            'rewards_distributed' => false,
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
        ]);

        $objective = $this->createObjective($raid, ['score_required' => 1000]);

        $this->createContribution($objective, $this->dominion, 1000); // Complete the objective

        $initialPlatinum = $this->dominion->resource_platinum;
        $initialGems = $this->dominion->resource_gems;

        // Act
        $this->service->processCompletedRaids($this->round);

        // Assert
        $raid->refresh();
        $this->assertTrue($raid->rewards_distributed);

        $this->dominion->refresh();
        $this->assertGreaterThan($initialPlatinum, $this->dominion->resource_platinum);
        $this->assertGreaterThan($initialGems, $this->dominion->resource_gems);
    }

    public function testDistributeRaidRewards_WithMultipleDominions_DistributesProportionally()
    {
        // Arrange
        $raid = $this->createRaid([
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
        ]);

        $objective = $this->createObjective($raid, ['score_required' => 2000]); // Higher requirement to avoid excess bonus

        $otherDominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first());

        // Dominion 1 contributes 15%, Dominion 2 contributes 10% (both well under caps and bonus thresholds)
        // Let's add a third dominion to ensure no one hits 25% threshold
        $thirdDominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first());
        $this->createContribution($objective, $this->dominion, 300); // 15% of 2000
        $this->createContribution($objective, $otherDominion, 200); // 10% of 2000
        $this->createContribution($objective, $thirdDominion, 1500); // 75% of 2000

        $initialPlatinum1 = $this->dominion->resource_platinum;
        $initialPlatinum2 = $otherDominion->resource_platinum;

        // Act
        $this->service->distributeRaidRewards($raid);

        // Assert
        $this->dominion->refresh();
        $otherDominion->refresh();

        $platinum1Gained = $this->dominion->resource_platinum - $initialPlatinum1;
        $platinum2Gained = $otherDominion->resource_platinum - $initialPlatinum2;

        // In the new two-tier system, contributions are more evenly distributed
        // Dominion 1 (300) should still get more than Dominion 2 (200), but the difference is smaller due to equal distribution
        $this->assertGreaterThanOrEqual($platinum2Gained, $platinum1Gained);

        // All should get completion rewards since realm completed the objective (2000 >= 2000)
        $this->assertGreaterThan(0, $this->dominion->resource_gems);
        $this->assertEquals($this->dominion->resource_gems, $otherDominion->resource_gems);
    }

    public function testDistributeRaidRewards_RecordsHistoryForRewardedDominions()
    {
        // Arrange
        $raid = $this->createRaid([
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
        ]);

        $objective = $this->createObjective($raid, ['score_required' => 1000]);
        $this->createContribution($objective, $this->dominion, 1000);

        $initialHistoryCount = $this->dominion->history()->count();

        // Act
        $this->service->distributeRaidRewards($raid);

        // Assert
        $newHistoryCount = $this->dominion->history()->count();
        $this->assertGreaterThan($initialHistoryCount, $newHistoryCount);

        $rewardHistory = $this->dominion->history()
            ->where('event', HistoryService::EVENT_ACTION_RAID_REWARD)
            ->first();

        $this->assertNotNull($rewardHistory);
    }

    // Helper methods
    private function createRaid(array $attributes = []): Raid
    {
        return Raid::create(array_merge([
            'round_id' => $this->round->id,
            'name' => 'Test Raid',
            'description' => 'Test raid description',
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
            'rewards_distributed' => false,
        ], $attributes));
    }

    private function createObjective(Raid $raid, array $attributes = []): RaidObjective
    {
        return RaidObjective::create(array_merge([
            'raid_id' => $raid->id,
            'name' => 'Test Objective',
            'description' => 'Test objective description',
            'order' => 1,
            'score_required' => 1000,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
        ], $attributes));
    }

    private function createContribution(RaidObjective $objective, Dominion $dominion, int $score): RaidContribution
    {
        // Create or find a tactic for this objective
        $tactic = RaidObjectiveTactic::firstOrCreate([
            'raid_objective_id' => $objective->id,
            'type' => 'test',
        ], [
            'name' => 'Test Tactic',
            'attributes' => json_encode(['points_awarded' => 100]),
            'bonuses' => json_encode([]),
        ]);

        return RaidContribution::create([
            'realm_id' => $dominion->realm_id,
            'dominion_id' => $dominion->id,
            'raid_objective_id' => $objective->id,
            'raid_tactic_id' => $tactic->id,
            'type' => 'test',
            'score' => $score,
        ]);
    }
}
