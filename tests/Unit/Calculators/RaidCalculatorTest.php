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

    public function testGetObjectiveScore_WithNoContributions_ReturnsZero()
    {
        // Act
        $score = $this->calculator->getObjectiveScore($this->objective);

        // Assert
        $this->assertEquals(0, $score);
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

    public function testGetObjectiveProgress_WithNoContributions_ReturnsZero()
    {
        // Act
        $progress = $this->calculator->getObjectiveProgress($this->objective);

        // Assert
        $this->assertEquals(0.0, $progress);
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

    public function testIsObjectiveCompleted_WithInsufficientScore_ReturnsFalse()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 999,
        ]);

        // Act
        $completed = $this->calculator->isObjectiveCompleted($this->objective);

        // Assert
        $this->assertFalse($completed);
    }

    public function testIsObjectiveCompleted_WithSufficientScore_ReturnsTrue()
    {
        // Arrange
        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 1000,
        ]);

        // Act
        $completed = $this->calculator->isObjectiveCompleted($this->objective);

        // Assert
        $this->assertTrue($completed);
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

    // Tests for new unified API with realm parameter
    public function testGetObjectiveScore_WithRealm_ReturnsOnlyRealmScore()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        
        // Ensure they're in different realms by updating one
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

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
            'score' => 700,
        ]);

        // Act
        $realmScore = $this->calculator->getObjectiveScore($this->objective, $this->dominion->realm);
        $globalScore = $this->calculator->getObjectiveScore($this->objective);

        // Assert
        $this->assertEquals(300, $realmScore); // Only this realm's score
        $this->assertEquals(1000, $globalScore); // All realms combined
    }

    public function testGetObjectiveProgress_WithRealm_ReturnsOnlyRealmProgress()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        
        // Ensure they're in different realms by updating one
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 250, // 25% of 1000 required
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 750, // 75% of 1000 required
        ]);

        // Act
        $realmProgress = $this->calculator->getObjectiveProgress($this->objective, $this->dominion->realm);
        $globalProgress = $this->calculator->getObjectiveProgress($this->objective);

        // Assert
        $this->assertEquals(25.0, $realmProgress); // Only this realm's progress
        $this->assertEquals(100.0, $globalProgress); // All realms combined
    }

    public function testIsObjectiveCompleted_WithRealm_ChecksOnlyRealmCompletion()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        
        // Ensure they're in different realms by updating one
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 500, // 50% - not completed
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 1000, // 100% - completed
        ]);

        // Act
        $realmCompleted = $this->calculator->isObjectiveCompleted($this->objective, $this->dominion->realm);
        $otherRealmCompleted = $this->calculator->isObjectiveCompleted($this->objective, $anotherDominion->realm);
        $globalCompleted = $this->calculator->isObjectiveCompleted($this->objective);

        // Assert
        $this->assertFalse($realmCompleted); // This realm hasn't completed
        $this->assertTrue($otherRealmCompleted); // Other realm has completed
        $this->assertTrue($globalCompleted); // At least one realm has completed
    }

    public function testGetRecentContributionsInRealm_ReturnsOnlyRealmContributions()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        
        // Ensure they're in different realms by updating one
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test1',
            'score' => 100,
            'created_at' => now()->subMinutes(1),
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test2',
            'score' => 200,
            'created_at' => now()->subMinutes(2),
        ]);

        // Act
        $realmContributions = $this->calculator->getRecentContributionsInRealm($this->objective, $this->dominion->realm, 5);
        $allContributions = $this->calculator->getRecentContributions($this->objective, 5);

        // Assert
        $this->assertCount(1, $realmContributions); // Only this realm's contribution
        $this->assertCount(2, $allContributions); // All contributions
        $this->assertEquals('test1', $realmContributions[0]['type']);
        $this->assertEquals($this->dominion->name, $realmContributions[0]['dominion_name']);
        $this->assertArrayNotHasKey('realm_name', $realmContributions[0]); // Should not include realm name
    }

    public function testGetTopContributorsInRealm_ReturnsOnlyRealmContributors()
    {
        // Arrange
        $anotherUser = $this->createUser();
        $anotherDominion = $this->createDominion($anotherUser, $this->round, Race::first());
        
        // Ensure they're in different realms by updating one
        $anotherRealm = $this->createRealm($this->round, 'Test Realm 2');
        $anotherDominion->realm_id = $anotherRealm->id;
        $anotherDominion->save();
        $realmMate = $this->createDominion($this->createUser(), $this->round, $this->dominion->race, $this->dominion->realm);

        RaidContribution::create([
            'realm_id' => $this->dominion->realm_id,
            'dominion_id' => $this->dominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 300,
        ]);

        RaidContribution::create([
            'realm_id' => $realmMate->realm_id,
            'dominion_id' => $realmMate->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 400,
        ]);

        RaidContribution::create([
            'realm_id' => $anotherDominion->realm_id,
            'dominion_id' => $anotherDominion->id,
            'raid_objective_id' => $this->objective->id,
            'type' => 'test',
            'score' => 500,
        ]);

        // Act
        $realmContributors = $this->calculator->getTopContributorsInRealm($this->objective, $this->dominion->realm, 5);
        $allContributors = $this->calculator->getTopContributors($this->objective, 5);

        // Assert
        $this->assertCount(2, $realmContributors); // Only this realm's contributors
        $this->assertCount(3, $allContributors); // All contributors
        $this->assertEquals(400, $realmContributors[0]['total_score']); // Highest in realm
        $this->assertEquals(300, $realmContributors[1]['total_score']); // Second in realm
        $this->assertArrayNotHasKey('realm_name', $realmContributors[0]); // Should not include realm name
    }
}