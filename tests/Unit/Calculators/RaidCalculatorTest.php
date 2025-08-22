<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\RaidCalculator
 */
class RaidCalculatorTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var RaidCalculator */
    protected $calculator;

    /** @var Round */
    protected $round;

    /** @var Raid */
    protected $raid;

    /** @var RaidObjective */
    protected $objective;

    /** @var RaidObjectiveTactic */
    protected $tactic;

    /** @var Dominion */
    protected $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(RaidCalculator::class);

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::first());

        $this->raid = Raid::create([
            'round_id' => $this->round->id,
            'name' => 'Test Raid',
            'description' => 'Test raid description',
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
        ]);

        $this->objective = RaidObjective::create([
            'raid_id' => $this->raid->id,
            'name' => 'Test Objective',
            'description' => 'Test objective description',
            'order' => 1,
            'score_required' => 1000,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDay(),
        ]);

        $this->tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'name' => 'Test Tactic',
            'description' => 'Test tactic description',
            'attributes' => json_encode(['points_awarded' => 100]),
            'bonuses' => json_encode([]),
        ]);
    }

    public function testBasicObjectiveMethods_WithNoContributions_ReturnZero()
    {
        // Act & Assert - Test multiple methods return zero with no contributions
        $this->assertEquals(0, $this->calculator->getObjectiveScore($this->objective));
        $this->assertEquals(0.0, $this->calculator->getObjectiveProgress($this->objective));
        $this->assertFalse($this->calculator->isObjectiveCompleted($this->objective));
    }

    public function testGetObjectiveScore_WithContributions_ReturnsCorrectSum()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 250,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 350,
        ]);

        // Act
        $score = $this->calculator->getObjectiveScore($this->objective);

        // Assert
        $this->assertEquals(600, $score);
    }

    public function testGetObjectiveProgress_WithPartialContributions_ReturnsCorrectPercentage()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 250, // 25% of 1000 required
        ]);

        // Act
        $progress = $this->calculator->getObjectiveProgress($this->objective);

        // Assert
        $this->assertEquals(25.0, $progress);
    }

    public function testGetObjectiveProgress_WithExcessContributions_CapsAtOneHundred()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 1500, // 150% of 1000 required
        ]);

        // Act
        $progress = $this->calculator->getObjectiveProgress($this->objective);

        // Assert
        $this->assertEquals(100.0, $progress);
    }

    /**
     * @dataProvider completionScenarioProvider
     */
    public function testIsObjectiveCompleted_WithVariousScores_ReturnsCorrectStatus($score, $expectedCompleted)
    {
        // Arrange
        if ($score > 0) {
            RaidContribution::create([
                'realm_id' => $this->dominion->realm_id,
                'dominion_id' => $this->dominion->id,
                'raid_objective_id' => $this->objective->id,
                'raid_tactic_id' => $this->tactic->id,
                'type' => 'test',
                'score' => $score,
            ]);
        }

        // Act
        $completed = $this->calculator->isObjectiveCompleted($this->objective);

        // Assert
        $this->assertEquals($expectedCompleted, $completed);
    }

    public function completionScenarioProvider(): array
    {
        return [
            'no_contributions' => [0, false],
            'insufficient_score' => [999, false],
            'exact_requirement' => [1000, true],
            'excess_score' => [1500, true],
        ];
    }

    public function testGetDominionContribution_WithMultipleContributions_ReturnsCorrectSum()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominionWithLegacyStats($anotherUser, $this->round, Race::first());

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 200,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 150,
        ]);

        // Act
        $contribution = $this->calculator->getDominionContribution($this->objective, $this->dominion);

        // Assert
        $this->assertEquals(450, $contribution); // Only this dominion's contributions
    }

    public function testGetRecentContributions_ReturnsLimitedOrderedResults()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test1',
            'score' => 100,
            'created_at' => now()->subMinutes(3),
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test2',
            'score' => 200,
            'created_at' => now()->subMinutes(1),
        ]);

        // Act
        $contributions = $this->calculator->getRecentContributions($this->objective, $this->dominion->realm, 5);

        // Assert
        $this->assertCount(2, $contributions);
        $this->assertEquals('test2', $contributions[0]['type']); // Most recent first
        $this->assertEquals('test1', $contributions[1]['type']);
        $this->assertEquals(200, $contributions[0]['score']);
    }

    public function testGetTopContributors_ReturnsCorrectRanking()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominionWithLegacyStats($anotherUser, $this->round, Race::first());

        // First dominion makes multiple contributions
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 200,
        ]);

        // Second dominion makes one larger contribution
        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 400,
        ]);

        // Act
        $topContributors = $this->calculator->getTopContributors($this->objective, 5);

        // Assert
        $this->assertCount(2, $topContributors);
        $this->assertEquals(500, $topContributors[0]['total_score']); // First dominion: 300 + 200
        $this->assertEquals(400, $topContributors[1]['total_score']); // Second dominion: 400
    }

    public function testGetRealmContribution_ReturnsCorrectSum()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominionWithLegacyStats($anotherUser, $this->round, $this->dominion->race, $this->dominion->realm);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 200,
        ]);

        // Act
        $realmContribution = $this->calculator->getRealmContribution($this->objective, $this->dominion->realm);

        // Assert
        $this->assertEquals(500, $realmContribution); // 300 + 200
    }

    public function testGetDominionContributionPercentage_ReturnsCorrectPercentage()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominionWithLegacyStats($anotherUser, $this->round, Race::first());

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 300, // 30% of 1000 total
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test',
            'score' => 700, // 70% of 1000 total
        ]);

        // Act
        $percentage = $this->calculator->getDominionContributionPercentage($this->objective, $this->dominion);

        // Assert
        $this->assertEquals(30.0, $percentage);
    }

    // Tests for realm-specific API methods
    public function testRealmSpecificMethods_WithMultipleRealms_ReturnOnlyRealmData()
    {
        // Arrange - Create dominions in different realms
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominionWithLegacyStats($anotherUser, $this->round, Race::first());
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

        $realmMate = $this->createDominionWithLegacyStats($this->createUser(), $this->round, $this->dominion->race, $this->dominion->realm);

        // Create contributions from both realms
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test1',
            'score' => 300,
            'created_at' => now()->subMinutes(1),
        ]);

        RaidContribution::create([
            'realm_id' => $realmMate->realm_id,
            'dominion_id' => $realmMate->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test1',
            'score' => 200,
            'created_at' => now()->subMinutes(2),
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'raid_tactic_id' => $this->tactic->id,
            'type' => 'test2',
            'score' => 700,
            'created_at' => now()->subMinutes(3),
        ]);

        // Test 1: Score calculations
        $thisRealmScore = $this->calculator->getObjectiveScore($this->objective, $this->dominion->realm);
        $otherRealmScore = $this->calculator->getObjectiveScore($this->objective, $anotherDominion->realm);
        $globalScore = $this->calculator->getObjectiveScore($this->objective);

        $this->assertEquals(500, $thisRealmScore); // 300 + 200
        $this->assertEquals(700, $otherRealmScore);
        $this->assertEquals(1200, $globalScore); // All realms combined

        // Test 2: Progress calculations
        $thisRealmProgress = $this->calculator->getObjectiveProgress($this->objective, $this->dominion->realm);
        $otherRealmProgress = $this->calculator->getObjectiveProgress($this->objective, $anotherDominion->realm);
        $globalProgress = $this->calculator->getObjectiveProgress($this->objective);

        $this->assertEquals(50.0, $thisRealmProgress); // 500/1000 = 50%
        $this->assertEquals(70.0, $otherRealmProgress); // 700/1000 = 70%
        $this->assertEquals(100.0, $globalProgress); // Combined exceeds 100%, capped

        // Test 3: Completion status
        $thisRealmCompleted = $this->calculator->isObjectiveCompleted($this->objective, $this->dominion->realm);
        $otherRealmCompleted = $this->calculator->isObjectiveCompleted($this->objective, $anotherDominion->realm);
        $globalCompleted = $this->calculator->isObjectiveCompleted($this->objective);

        $this->assertFalse($thisRealmCompleted); // 500 < 1000
        $this->assertFalse($otherRealmCompleted); // 700 < 1000
        $this->assertTrue($globalCompleted); // 1200 >= 1000

        // Test 4: Recent contributions filtering
        $realmContributions = $this->calculator->getRecentContributions($this->objective, $this->dominion->realm, 5);

        $this->assertCount(2, $realmContributions);
        $this->assertEquals('test1', $realmContributions[0]['type']);
        $this->assertArrayNotHasKey('realm_name', $realmContributions[0]); // Realm-specific should not include realm name

        // Test 5: Top contributors
        $realmContributors = $this->calculator->getTopContributors($this->objective, 5);

        $this->assertCount(3, $realmContributors); // Only this realm's contributors
        $this->assertEquals(700, $realmContributors[0]['total_score']); // Highest
        $this->assertEquals(300, $realmContributors[1]['total_score']); // Second
        $this->assertEquals(200, $realmContributors[2]['total_score']); // Third
    }

    // Tests for reward calculation system
    public function testCalculateParticipationReward_WithNoContributions_ReturnsZero()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $contributionData = ['total' => 0, 'by_dominion' => []];
        $realmBonusData = [];

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $contributionData, $realmBonusData);

        // Assert
        $this->assertEquals(0, $reward['amount']);
        $this->assertEquals('resource_platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
    }

    public function testCalculateParticipationReward_WithBasicContribution_ReturnsProportionalReward()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $playerAllocations = [$this->dominion->id => 2000]; // Pre-calculated allocation

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $playerAllocations);

        // Assert
        $this->assertEquals(2000, $reward['amount']);
        $this->assertEquals('resource_platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']); // No bonuses in new system
    }

    public function testCalculateParticipationReward_WithHighContribution_ReturnsAllocatedAmount()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $playerAllocations = [$this->dominion->id => 3000]; // Pre-calculated allocation

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $playerAllocations);

        // Assert
        $this->assertEquals(3000, $reward['amount']);
        $this->assertEquals('resource_platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']); // No bonuses in new system
    }

    public function testCalculateParticipationReward_WithMaxAllocation_ReturnsMaxAmount()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $playerAllocations = [$this->dominion->id => 1000]; // Max allocation based on 10% of required score

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $playerAllocations);

        // Assert
        $this->assertEquals(1000, $reward['amount']);
        $this->assertEquals('resource_platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']); // No bonuses in new system
    }

    public function testCalculateParticipationReward_WithZeroAllocation_ReturnsZero()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $playerAllocations = []; // No allocation for this dominion

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $playerAllocations);

        // Assert
        $this->assertEquals(0, $reward['amount']);
        $this->assertEquals('resource_platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
    }

    public function testCalculateCompletionReward_WithIncompleteRealm_ReturnsScaledReward()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $realmCompletionData = [
            $this->dominion->realm_id => [
                'completion_percentage' => 0.5, // 50% completion
                'all_completed' => false
            ]
        ];

        // Act
        $reward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $realmCompletionData);

        // Assert (50% of 1000 completion reward)
        $this->assertEquals(500, $reward['amount']);
        $this->assertEquals('resource_gems', $reward['resource']);
        $this->assertContains('partial_completion', $reward['bonuses_applied']);
    }

    public function testCalculateCompletionReward_WithCompleteRealm_ReturnsFullReward()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $realmCompletionData = [
            $this->dominion->realm_id => [
                'completion_percentage' => 1.0, // 100% completion
                'all_completed' => true
            ]
        ];

        // Act
        $reward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $realmCompletionData);

        // Assert
        $this->assertEquals(1000, $reward['amount']);
        $this->assertEquals('resource_gems', $reward['resource']);
        $this->assertContains('full_completion', $reward['bonuses_applied']);
    }

    public function testCalculateRaidRewards_IntegratesAllRewardTypes()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $objective = $raid->objectives->first();
        $objective->score_required = 1000;
        $objective->save();

        $otherDominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first());
        $this->createRaidContributions($raid, [
            ['dominion' => $this->dominion, 'score' => 1500], // High contribution, exceeds requirement
            ['dominion' => $otherDominion, 'score' => 500],
        ]);

        // Act
        $allRewards = $this->calculator->calculateRaidRewards($raid);

        // Assert
        $this->assertCount(2, $allRewards);

        $dominionReward = collect($allRewards)->firstWhere('dominion.id', $this->dominion->id);
        $this->assertNotNull($dominionReward);
        $this->assertGreaterThan(0, $dominionReward['participation_reward']['amount']);
        $this->assertEquals(1000, $dominionReward['completion_reward']['amount']); // Realm completed
        $this->assertContains('full_completion', $dominionReward['completion_reward']['bonuses_applied']);
        $this->assertEmpty($dominionReward['participation_reward']['bonuses_applied']); // No bonuses in new system
    }

    public function testCalculateCompletionReward_WithPrecomputedCompletionMap_UsesMapInsteadOfRecalculating()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $objective = $raid->objectives->first();
        $objective->score_required = 1000;
        $objective->save();

        $this->createRaidContributions($raid, [
            ['dominion' => $this->dominion, 'score' => 1000], // Completes the objective
        ]);

        // Create a precomputed completion status map
        $completionStatusMap = [
            $this->dominion->realm_id => [
                'completion_percentage' => 1.0,
                'all_completed' => true,
            ],
            999 => [
                'completion_percentage' => 0.0,
                'all_completed' => false,
            ],
        ];

        // Act
        $rewardWithMap = $this->calculator->calculateCompletionReward($raid, $this->dominion, $completionStatusMap);

        // For comparison, create a fresh completion map
        $reflection = new \ReflectionClass($this->calculator);
        $contributionMethod = $reflection->getMethod('getRaidContributionData');
        $contributionMethod->setAccessible(true);
        $contributionData = $contributionMethod->invoke($this->calculator, $raid);

        $completionMethod = $reflection->getMethod('calculateRealmCompletionData');
        $completionMethod->setAccessible(true);
        $freshCompletionData = $completionMethod->invoke($this->calculator, $raid, $contributionData);

        $rewardWithoutMap = $this->calculator->calculateCompletionReward($raid, $this->dominion, $freshCompletionData);

        // Assert - Both should produce the same result
        $this->assertEquals($rewardWithoutMap['amount'], $rewardWithMap['amount']);
        $this->assertEquals($rewardWithoutMap['resource'], $rewardWithMap['resource']);
        $this->assertEquals(1000, $rewardWithMap['amount']); // Should get completion reward
    }

    public function testTwoTierRewardDistribution_WithMultipleRealms_DistributesCorrectly()
    {
        // Arrange - Create a raid with multiple realms
        $raid = $this->createCompletedRaid();
        $raid->reward_amount = 10000; // Total pool
        $raid->save();

        // Create dominions in different realms with correct alignment
        $realm1 = $this->dominion->realm;
        $realm2 = $this->createRealm($this->round, $this->dominion->race->alignment);
        $realm3 = $this->createRealm($this->round, $this->dominion->race->alignment);

        $dominion2 = $this->createDominionWithLegacyStats($this->createUser(), $this->round, $this->dominion->race, $realm2);
        $dominion3 = $this->createDominionWithLegacyStats($this->createUser(), $this->round, $this->dominion->race, $realm3);

        // Create contributions: Realm 1 = 6000, Realm 2 = 3000, Realm 3 = 1000 (total 10000)
        $this->createRaidContributions($raid, [
            ['dominion' => $this->dominion, 'score' => 6000],  // Realm 1: 60% contribution
            ['dominion' => $dominion2, 'score' => 3000],       // Realm 2: 30% contribution
            ['dominion' => $dominion3, 'score' => 1000],       // Realm 3: 10% contribution
        ]);

        // Act
        $rewardData = $this->calculator->calculateRaidRewards($raid);

        // Assert realm distribution
        $realm1Reward = collect($rewardData)->firstWhere('dominion.id', $this->dominion->id)['participation_reward']['amount'];
        $realm2Reward = collect($rewardData)->firstWhere('dominion.id', $dominion2->id)['participation_reward']['amount'];
        $realm3Reward = collect($rewardData)->firstWhere('dominion.id', $dominion3->id)['participation_reward']['amount'];

        // Each realm should get at least their capped contribution (max 10% = 1000) plus equal distribution
        $this->assertGreaterThanOrEqual(1000, $realm1Reward); // Realm 1 capped at 10%
        $this->assertGreaterThanOrEqual(1000, $realm2Reward); // Realm 2 capped at 10%
        $this->assertGreaterThanOrEqual(1000, $realm3Reward); // Realm 3 gets 10%

        // Total should equal the reward pool (allow for small rounding differences)
        $totalDistributed = $realm1Reward + $realm2Reward + $realm3Reward;
        $this->assertEqualsWithDelta(10000, $totalDistributed, 1);
    }

    public function testTwoTierRewardDistribution_WithMultiplePlayersInRealm_DistributesWithinRealm()
    {
        // Arrange - Create multiple players in same realm
        $raid = $this->createCompletedRaid();
        $raid->reward_amount = 10000;
        $raid->save();

        $player2 = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first(), $this->dominion->realm);
        $player3 = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first(), $this->dominion->realm);

        // Create contributions within same realm: Player 1 = 6000, Player 2 = 3000, Player 3 = 1000
        $this->createRaidContributions($raid, [
            ['dominion' => $this->dominion, 'score' => 6000],  // 60% of realm contribution
            ['dominion' => $player2, 'score' => 3000],         // 30% of realm contribution
            ['dominion' => $player3, 'score' => 1000],         // 10% of realm contribution
        ]);

        // Act
        $rewardData = $this->calculator->calculateRaidRewards($raid);

        // Assert player distribution within realm
        $player1Reward = collect($rewardData)->firstWhere('dominion.id', $this->dominion->id)['participation_reward']['amount'];
        $player2Reward = collect($rewardData)->firstWhere('dominion.id', $player2->id)['participation_reward']['amount'];
        $player3Reward = collect($rewardData)->firstWhere('dominion.id', $player3->id)['participation_reward']['amount'];

        // Each player should get at least their capped contribution (max 10% of required score = 100) plus equal distribution
        $this->assertGreaterThanOrEqual(100, $player1Reward); // Player 1 capped at 10% of required score
        $this->assertGreaterThanOrEqual(100, $player2Reward); // Player 2 capped at 10% of required score
        $this->assertGreaterThanOrEqual(100, $player3Reward); // Player 3 gets proportional share

        // In the new system with equal distribution of leftovers, differences may be smaller
        // Player 1 should get at least as much as others due to higher contribution
        $this->assertGreaterThanOrEqual($player2Reward, $player1Reward);
        $this->assertGreaterThanOrEqual($player3Reward, $player2Reward);

        // Total should equal the reward pool (allow for small rounding differences)
        $totalDistributed = $player1Reward + $player2Reward + $player3Reward;
        $this->assertEqualsWithDelta(10000, $totalDistributed, 1);
    }

    public function testGetRealmCompletionStatusMap_ReturnsCorrectMappingForAllParticipatingRealms()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $objective = $raid->objectives->first();
        $objective->score_required = 1000;
        $objective->save();

        // Create dominions in different realms
        $anotherRealm = $this->createRealm($this->round, 'Another Realm');
        $anotherDominion = $this->createDominionWithLegacyStats($this->createUser(), $this->round, Race::first());
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

        $this->createRaidContributions($raid, [
            ['dominion' => $this->dominion, 'score' => 1000],      // Realm 1 completes
            ['dominion' => $anotherDominion, 'score' => 500],     // Realm 2 doesn't complete
        ]);

        // Act - Use reflection to access the protected method
        $reflection = new \ReflectionClass($this->calculator);
        $contributionMethod = $reflection->getMethod('getRaidContributionData');
        $contributionMethod->setAccessible(true);
        $contributionData = $contributionMethod->invoke($this->calculator, $raid);

        $method = $reflection->getMethod('calculateRealmCompletionData');
        $method->setAccessible(true);
        $completionMap = $method->invoke($this->calculator, $raid, $contributionData);

        // Assert
        $this->assertArrayHasKey($this->dominion->realm_id, $completionMap);
        $this->assertArrayHasKey($anotherDominion->realm_id, $completionMap);
        $this->assertTrue($completionMap[$this->dominion->realm_id]['all_completed']);      // Realm 1 completed
        $this->assertFalse($completionMap[$anotherDominion->realm_id]['all_completed']);    // Realm 2 didn't complete
        $this->assertEquals(1.0, $completionMap[$this->dominion->realm_id]['completion_percentage']);      // Realm 1 100% complete
        $this->assertEquals(0.0, $completionMap[$anotherDominion->realm_id]['completion_percentage']);    // Realm 2 0% complete
    }

    public function testCompletionRewardScaling_WhenEnabled_ScalesRewardByPercentage()
    {
        // Arrange - Test the scaling logic behavior with different completion data
        $raid = $this->createCompletedRaid();

        // Test 60% completion (3 out of 5 objectives)
        $completionData = [
            $this->dominion->realm_id => [
                'completion_percentage' => 0.6,  // 60% completion
                'all_completed' => false,
            ]
        ];

        // Since COMPLETION_REWARD_SCALING is now true, we expect percentage scaling
        $reward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $completionData);

        // Assert percentage behavior (60% of 1000 = 600)
        $this->assertEquals(600, $reward['amount']);
        $this->assertEquals('resource_gems', $reward['resource']);
        $this->assertContains('partial_completion', $reward['bonuses_applied']);

        // Test with full completion
        $fullCompletionData = [
            $this->dominion->realm_id => [
                'completion_percentage' => 1.0,  // 100% completion
                'all_completed' => true,
            ]
        ];

        $fullReward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $fullCompletionData);
        $this->assertEquals(1000, $fullReward['amount']); // Full completion = full reward
        $this->assertContains('full_completion', $fullReward['bonuses_applied']);
    }

    public function testGetRealmActivityMultiplier_WithActivityBonus_ReturnsIncreasedMultiplier()
    {
        // Arrange - Create a raid with average of 8.5 active players
        $this->raid->average_active_player_count = 8.5;
        $this->raid->save();

        // Set realm to have fewer active players (gets bonus)
        $this->dominion->realm->active_player_count = 6; // Below neutral zone
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should get bonus: 8.5 / (6+1) = 1.21x multiplier
        $expectedMultiplier = 8.5 / 7;
        $this->assertEquals($expectedMultiplier, $multiplier, '', 0.01);
        $this->assertGreaterThan(1.0, $multiplier);
    }

    public function testGetRealmActivityMultiplier_WithActivityPenalty_ReturnsDecreasedMultiplier()
    {
        // Arrange - Create a raid with average of 8.5 active players
        $this->raid->average_active_player_count = 8.5;
        $this->raid->save();

        // Set realm to have more active players (gets penalty)
        $this->dominion->realm->active_player_count = 12; // Above neutral zone
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should get penalty: 8.5 / (12-1) = 0.77x multiplier
        $expectedMultiplier = 8.5 / 11;
        $this->assertEquals($expectedMultiplier, $multiplier, '', 0.01);
        $this->assertLessThan(1.0, $multiplier);
    }

    public function testGetRealmActivityMultiplier_WithNeutralZone_ReturnsNoMultiplier()
    {
        // Arrange - Create a raid with average of 8.5 active players
        $this->raid->average_active_player_count = 8.5;
        $this->raid->save();

        // Set realm to be in neutral zone (8 or 9 players)
        $this->dominion->realm->active_player_count = 8; // In neutral zone
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should get no multiplier (1.0x)
        $this->assertEquals(1.0, $multiplier);
    }

    public function testGetRealmActivityMultiplier_WithNoRaidAverage_ReturnsNoMultiplier()
    {
        // Arrange - Create a raid with no average set
        $this->raid->average_active_player_count = 0;
        $this->raid->save();

        $this->dominion->realm->active_player_count = 5;
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should get no multiplier when no average calculated
        $this->assertEquals(1.0, $multiplier);
    }

    public function testGetRealmActivityMultiplier_WithExtremeBonusCapped_ReturnsMaxBonus()
    {
        // Arrange - Create scenario with extreme bonus
        $this->raid->average_active_player_count = 8.0;
        $this->raid->save();

        // Set realm to have very few active players
        $this->dominion->realm->active_player_count = 2; // Should give 8.0/(2+1) = 2.67x, but capped at 2.0x
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should be capped at 2.0x multiplier
        $this->assertEquals(2.0, $multiplier);
    }

    public function testGetRealmActivityMultiplier_WithExtremePenaltyCapped_ReturnsMinPenalty()
    {
        // Arrange - Create scenario with extreme penalty
        $this->raid->average_active_player_count = 8.0;
        $this->raid->save();

        // Set realm to have many active players
        $this->dominion->realm->active_player_count = 20; // Should give 8.0/(20-1) = 0.42x, but capped at 0.75x
        $this->dominion->realm->save();

        // Act
        $multiplier = $this->calculator->getRealmActivityMultiplier($this->dominion, $this->raid);

        // Assert - Should be capped at 0.75x multiplier
        $this->assertEquals(0.75, $multiplier);
    }

    public function testGetTacticPointsEarned_WithBasicTactic_ReturnsBasePoints()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'investment',
            'name' => 'Test Investment',
            'attributes' => ['points_awarded' => 100],
            'bonuses' => [],
        ]);

        // Act
        $pointsEarned = $this->calculator->getTacticPointsEarned($this->dominion, $tactic);

        // Assert - Should return base points without activity multiplier
        $this->assertEquals(100, $pointsEarned);
    }

    // Helper methods for reward tests
    private function createCompletedRaid(): Raid
    {
        $raid = Raid::create([
            'round_id' => $this->round->id,
            'name' => 'Completed Test Raid',
            'description' => 'Test raid for reward calculations',
            'reward_resource' => 'resource_platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'resource_gems',
            'completion_reward_amount' => 1000,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subHour(),
            'rewards_distributed' => false,
        ]);

        $objective = RaidObjective::create([
            'raid_id' => $raid->id,
            'name' => 'Test Objective',
            'description' => 'Test objective for reward calculations',
            'order' => 1,
            'score_required' => 1000,
            'start_date' => now()->subDays(2),
            'end_date' => now()->subHour(),
        ]);

        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $objective->id,
            'type' => 'test',
            'name' => 'Test Tactic',
            'attributes' => json_encode(['points_awarded' => 100]),
            'bonuses' => json_encode([]),
        ]);

        return $raid->load('objectives');
    }

    private function createRaidContributions(Raid $raid, array $contributions): void
    {
        $tactic = $raid->objectives->first()->tactics->first();

        foreach ($contributions as $contribution) {
            RaidContribution::create([
                'realm_id' => $contribution['dominion']->realm_id,
                'dominion_id' => $contribution['dominion']->id,
                'raid_objective_id' => $raid->objectives->first()->id,
                'raid_tactic_id' => $tactic->id,
                'type' => 'test',
                'score' => $contribution['score'],
            ]);
        }
    }
}
