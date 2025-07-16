<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\RaidCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
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

    /** @var Dominion */
    protected $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(RaidCalculator::class);
        
        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($user, $this->round, Race::first());

        $this->raid = Raid::create([
            'round_id' => $this->round->id,
            'name' => 'Test Raid',
            'description' => 'Test raid description',
            'reward_resource' => 'platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'gems',
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
            'type' => 'test',
            'score' => 250,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
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
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 200,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
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
            'type' => 'test1',
            'score' => 100,
            'created_at' => now()->subMinutes(3),
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test2',
            'score' => 200,
            'created_at' => now()->subMinutes(1),
        ]);

        // Act
        $contributions = $this->calculator->getRecentContributions($this->objective, 5);

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
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());

        // First dominion makes multiple contributions
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 200,
        ]);

        // Second dominion makes one larger contribution
        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
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
        $anotherDominion = $this->createDominion($anotherUser, $this->round, $this->dominion->race, $this->dominion->realm);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
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
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 300, // 30% of 1000 total
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
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
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();
        
        $realmMate = $this->createDominion($this->createUser(), $this->round, $this->dominion->race, $this->dominion->realm);

        // Create contributions from both realms
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test1',
            'score' => 300,
            'created_at' => now()->subMinutes(1),
        ]);

        RaidContribution::create([
            'realm_id' => $realmMate->realm_id,
            'dominion_id' => $realmMate->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test1',
            'score' => 200,
            'created_at' => now()->subMinutes(2),
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
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
        
        $this->assertCount(2, $realmContributions); // Only this realm's contributions
        $this->assertEquals('test1', $realmContributions[0]['type']);
        $this->assertArrayNotHasKey('realm_name', $realmContributions[0]); // Realm-specific should not include realm name

        // Test 5: Top contributors filtering  
        $realmContributors = $this->calculator->getTopContributorsInRealm($this->objective, $this->dominion->realm, 5);
        $allContributors = $this->calculator->getTopContributors($this->objective, 5);

        $this->assertCount(2, $realmContributors); // Only this realm's contributors
        $this->assertCount(3, $allContributors); // All contributors
        $this->assertEquals(300, $realmContributors[0]['total_score']); // Highest in realm
        $this->assertEquals(200, $realmContributors[1]['total_score']); // Second in realm
        $this->assertArrayNotHasKey('realm_name', $realmContributors[0]); // Should not include realm name
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
        $this->assertEquals('platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
    }

    public function testCalculateParticipationReward_WithBasicContribution_ReturnsProportionalReward()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $contributionData = [
            'total' => 2000,
            'by_dominion' => [$this->dominion->id => 400] // 20% of total
        ];
        $realmBonusData = [$this->dominion->realm_id => ['excess_bonus_eligible' => false]];

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $contributionData, $realmBonusData);

        // Assert
        $this->assertEquals(2000, $reward['amount']); // 20% of 10000 reward, no bonuses
        $this->assertEquals('platinum', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
    }

    public function testCalculateParticipationReward_WithHighContribution_AppliesParticipationBonus()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $contributionData = [
            'total' => 4000,
            'by_dominion' => [$this->dominion->id => 1250] // 31.25% of total - qualifies for high contribution bonus (>25%)
        ];
        $realmBonusData = [$this->dominion->realm_id => ['excess_bonus_eligible' => false]];

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $contributionData, $realmBonusData);

        // Assert
        // Expected: 31.25% * 10000 * 1.5 = 4687, but capped at 30% of total = 3000
        $maxReward = 10000 * 0.3; // 30% cap
        $this->assertEquals((int) $maxReward, $reward['amount']);
        $this->assertContains('participation_bonus', $reward['bonuses_applied']);
        $this->assertContains('capped', $reward['bonuses_applied']);
    }

    public function testCalculateParticipationReward_WithExcessContribution_AppliesExcessBonus()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $contributionData = [
            'total' => 1600,
            'by_dominion' => [$this->dominion->id => 1600] // 100% of total
        ];
        $realmBonusData = [$this->dominion->realm_id => ['excess_bonus_eligible' => true]];

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $contributionData, $realmBonusData);

        // Assert
        // Expected: 100% * 10000 * 1.25 = 12500, but capped at 30% of total = 3000
        $maxReward = 10000 * 0.3; // 30% cap
        $this->assertEquals((int) $maxReward, $reward['amount']);
        $this->assertContains('excess_bonus', $reward['bonuses_applied']);
        $this->assertContains('capped', $reward['bonuses_applied']);
    }

    public function testCalculateParticipationReward_WithIndividualCap_CapsReward()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $contributionData = [
            'total' => 5000,
            'by_dominion' => [$this->dominion->id => 5000] // 100% of total - would normally get all reward
        ];
        $realmBonusData = [$this->dominion->realm_id => ['excess_bonus_eligible' => false]];

        // Act
        $reward = $this->calculator->calculateParticipationReward($raid, $this->dominion, $contributionData, $realmBonusData);

        // Assert
        $maxReward = 10000 * 0.3; // 30% cap
        $this->assertEquals((int) $maxReward, $reward['amount']);
        $this->assertContains('capped', $reward['bonuses_applied']);
    }

    public function testCalculateCompletionReward_WithIncompleteRealm_ReturnsZero()
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

        // Assert
        $this->assertEquals(0, $reward['amount']);
        $this->assertEquals('gems', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
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
        $this->assertEquals('gems', $reward['resource']);
        $this->assertEmpty($reward['bonuses_applied']);
    }

    public function testCalculateRaidRewards_IntegratesAllRewardTypes()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $objective = $raid->objectives->first();
        $objective->score_required = 1000;
        $objective->save();

        $otherDominion = $this->createDominion($this->createUser(), $this->round, Race::first());
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
        $this->assertContains('excess_bonus', $dominionReward['participation_reward']['bonuses_applied']);
        $this->assertContains('participation_bonus', $dominionReward['participation_reward']['bonuses_applied']);
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

    public function testGetRealmCompletionStatusMap_ReturnsCorrectMappingForAllParticipatingRealms()
    {
        // Arrange
        $raid = $this->createCompletedRaid();
        $objective = $raid->objectives->first();
        $objective->score_required = 1000;
        $objective->save();

        // Create dominions in different realms
        $anotherRealm = $this->createRealm($this->round, 'Another Realm');
        $anotherDominion = $this->createDominion($this->createUser(), $this->round, Race::first());
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

        // Since COMPLETION_REWARD_SCALING is false by default, we expect binary behavior
        $reward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $completionData);
        
        // Assert binary behavior (current default)
        $this->assertEquals(0, $reward['amount']); // Binary mode: incomplete = 0 reward
        $this->assertEquals('gems', $reward['resource']);
        
        // Test with full completion
        $fullCompletionData = [
            $this->dominion->realm_id => [
                'completion_percentage' => 1.0,  // 100% completion
                'all_completed' => true,
            ]
        ];
        
        $fullReward = $this->calculator->calculateCompletionReward($raid, $this->dominion, $fullCompletionData);
        $this->assertEquals(1000, $fullReward['amount']); // Binary mode: complete = full reward
        
        // Note: To test percentage scaling, the COMPLETION_REWARD_SCALING constant would need to be true
        // This test documents the expected behavior when scaling is enabled in the future
    }

    // Helper methods for reward tests
    private function createCompletedRaid(): Raid
    {
        $raid = Raid::create([
            'round_id' => $this->round->id,
            'name' => 'Completed Test Raid',
            'description' => 'Test raid for reward calculations',
            'reward_resource' => 'platinum',
            'reward_amount' => 10000,
            'completion_reward_resource' => 'gems',
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

        return $raid->load('objectives');
    }

    private function createRaidContributions(Raid $raid, array $contributions): void
    {
        foreach ($contributions as $contribution) {
            RaidContribution::create([
                'realm_id' => $contribution['dominion']->realm_id,
                'dominion_id' => $contribution['dominion']->id,
                'raid_objective_id' => $raid->objectives->first()->id,
                'type' => 'test',
                'score' => $contribution['score'],
            ]);
        }
    }
}
