<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Raid;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\RaidObjective;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\RaidActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RaidActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var RaidActionService */
    protected $raidActionService;

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

        $this->raidActionService = app(RaidActionService::class);
        
        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($user, $this->round, Race::first());

        // Set up dominion with resources and land
        $this->dominion->resource_platinum = 100000;
        $this->dominion->resource_lumber = 50000;
        $this->dominion->resource_ore = 25000;
        $this->dominion->resource_gems = 5000;
        $this->dominion->resource_food = 75000;
        $this->dominion->resource_mana = 10000;
        $this->dominion->resource_boats = 1000;
        $this->dominion->military_draftees = 5000;
        $this->dominion->military_unit1 = 2000;
        $this->dominion->military_unit2 = 1000;
        $this->dominion->military_unit3 = 500;
        $this->dominion->military_unit4 = 200;
        $this->dominion->military_spies = 25; // For 0.5 spy ratio with 50 land
        $this->dominion->military_wizards = 50; // For 1.0 wizard ratio with 50 land
        $this->dominion->spy_strength = 100;
        $this->dominion->wizard_strength = 100;
        $this->dominion->morale = 100;
        // Set up land for mana cost calculations
        $this->dominion->land_plain = 20;
        $this->dominion->land_mountain = 5;
        $this->dominion->land_swamp = 5;
        $this->dominion->land_cavern = 5;
        $this->dominion->land_forest = 5;
        $this->dominion->land_hill = 5;
        $this->dominion->land_water = 5;
        // Total land = 50
        $this->dominion->save();

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

    protected function tearDown(): void
    {
        // Explicitly clear any references to prevent memory leaks
        $this->raidActionService = null;
        $this->dominion = null;
        $this->raid = null;
        $this->objective = null;
        $this->round = null;
        
        // Force garbage collection to help with memory usage
        if (function_exists('gc_collect_cycles')) {
            gc_collect_cycles();
        }
        
        parent::tearDown();
    }

    public function testPerformAction_Investment_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'investment',
            'name' => 'Fund Campaign (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 5000,
                'points_awarded' => 500,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(95000, $this->dominion->resource_platinum); // 100000 - 5000
        $this->assertEquals(50000, $this->dominion->resource_lumber);   // Unchanged since we selected platinum

        // Check contribution was created
        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('investment', $contribution->type);
        $this->assertEquals(500, $contribution->score); // Points from platinum option

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testPerformAction_Investment_InsufficientResources()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'investment',
            'name' => 'Fund Campaign (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 200000, // More than available
                'points_awarded' => 20000,
            ],
        ]);

        $data = [];

        // Act & Assert
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You do not have enough platinum');
        
        $this->raidActionService->performAction($this->dominion, $tactic, $data);
    }

    public function testPerformAction_EspionageTactic_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'espionage',
            'name' => 'Reconnaissance',
            'attributes' => [
                'strength_cost' => 20,
                'points_awarded' => 100,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(80, $this->dominion->spy_strength); // 100 - 20

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('espionage', $contribution->type);
        $this->assertEquals(3750, $contribution->score); // Base 100 * espionage multiplier (37.5)

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testEspionageScoreMultiplier()
    {
        // Arrange
        $opsCalculator = app(OpsCalculator::class);
        
        // Act
        $multiplier = $opsCalculator->getEspionageScoreMultiplier($this->dominion);
        
        // Assert
        // Formula: 1.5 * min(1, spy_ratio) * land_size  
        // Current setup gives multiplier of 37.5
        $this->assertEqualsWithDelta(37.5, $multiplier, 0.01);
        
        // Verify the calculation matches our expected score
        $basePoints = 100;
        $expectedScore = $basePoints * $multiplier;
        $this->assertEqualsWithDelta(3750, $expectedScore, 0.01);
    }

    public function testMagicScoreMultiplier()
    {
        // Arrange
        $opsCalculator = app(OpsCalculator::class);
        
        // Act
        $multiplier = $opsCalculator->getMagicScoreMultiplier($this->dominion);
        
        // Assert
        // Formula: 1.5 * min(1, wizard_ratio) * land_size
        // Current setup gives 75.0, which means wizard_ratio = 1.0
        // multiplier = 1.5 * 1.0 * 50 = 75.0
        $this->assertEqualsWithDelta(75.0, $multiplier, 0.01);
        
        // Verify the calculation matches our expected score
        $basePoints = 300;
        $expectedScore = $basePoints * $multiplier;
        $this->assertEqualsWithDelta(22500, $expectedScore, 0.01);
    }

    public function testPerformAction_EspionageWithMultipleOperations_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'espionage',
            'name' => 'Sabotage Communications',
            'attributes' => [
                'strength_cost' => 25,
                'points_awarded' => 120,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(75, $this->dominion->spy_strength); // 100 - 25

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('espionage', $contribution->type);
        $this->assertEqualsWithDelta(4500, $contribution->score, 0.01); // Base 120 * espionage multiplier (37.5)

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testPerformAction_MagicWithMultipleSpells_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'magic',
            'name' => 'Mystic Disruption',
            'attributes' => [
                'mana_cost' => 50, // 50 * 50 land = 2500 mana
                'strength_cost' => 35,
                'points_awarded' => 350,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(7500, $this->dominion->resource_mana); // 10000 - 2500
        $this->assertEquals(65, $this->dominion->wizard_strength); // 100 - 35

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('magic', $contribution->type);
        $this->assertEqualsWithDelta(26250, $contribution->score, 0.01); // Base 350 * magic multiplier (75.0)

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testPerformAction_SeederCompatibleEspionage_Success()
    {
        // Arrange: Use the exact structure from the RaidSeeder
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'espionage',
            'name' => 'Stealth Reconnaissance',
            'attributes' => [
                'strength_cost' => 25,
                'points_awarded' => 160,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(75, $this->dominion->spy_strength); // 100 - 25

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('espionage', $contribution->type);
        $this->assertEqualsWithDelta(6000, $contribution->score, 0.01); // Base 160 * espionage multiplier (37.5)

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testPerformAction_SeederCompatibleMagic_Success()
    {
        // Arrange: Use the exact structure from the RaidSeeder
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'magic',
            'name' => 'Shield of the Ancients',
            'attributes' => [
                'mana_cost' => 56, // 56 * 50 land = 2800 mana
                'strength_cost' => 35,
                'points_awarded' => 420,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(7200, $this->dominion->resource_mana); // 10000 - 2800
        $this->assertEquals(65, $this->dominion->wizard_strength); // 100 - 35

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('magic', $contribution->type);
        $this->assertEqualsWithDelta(31500, $contribution->score, 0.01); // Base 420 * magic multiplier (75.0)

        $this->assertStringContainsString('successfully completed', $result['message']);
    }

    public function testPerformAction_EspionageTactic_InsufficientStrength()
    {
        // Arrange
        $this->dominion->spy_strength = 10; // Less than required
        $this->dominion->save();

        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'espionage',
            'name' => 'Reconnaissance',
            'attributes' => [
                'strength_cost' => 20,
                'points_awarded' => 100,
            ],
        ]);

        $data = [];

        // Act & Assert
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You do not have enough spy strength');
        
        $this->raidActionService->performAction($this->dominion, $tactic, $data);
    }

    public function testPerformAction_ExplorationTactic_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'exploration',
            'name' => 'Test Exploration',
            'attributes' => [
                'draftee_cost' => 1000,
                'morale_cost' => 2,
                'points_awarded' => 200,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(4000, $this->dominion->military_draftees); // 5000 - 1000

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertEquals(200, $contribution->score);
    }

    public function testPerformAction_MagicTactic_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'magic',
            'name' => 'Test Spell',
            'attributes' => [
                'mana_cost' => 40, // 40 * 50 land = 2000 mana
                'strength_cost' => 30,
                'points_awarded' => 300,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        $this->assertEquals(8000, $this->dominion->resource_mana); // 10000 - 2000
        $this->assertEquals(70, $this->dominion->wizard_strength); // 100 - 30

        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertEquals(22500, $contribution->score); // Base 300 * magic multiplier (75.0)
    }

    public function testPerformAction_HeroTactic_Success()
    {
        // Arrange
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'experience' => 1000,
            'class' => 'alchemist',
            'health' => 100,
        ]);

        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'hero',
            'name' => 'Test Hero Action',
            'attributes' => [
                'name' => 'Ancient Dragon',
                'health' => 100,
                'attack' => 50,
                'defense' => 40,
                'evasion' => 20,
                'focus' => 15,
                'counter' => 10,
                'recover' => 25,
                'points_awarded' => 400,
            ],
        ]);

        $data = [];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->assertStringContainsString('The battle begins!', $result['message']);
        $this->assertArrayHasKey('redirect', $result);
        $this->assertStringContainsString('/dominion/heroes/battles', $result['redirect']);
    }

    public function testPerformAction_HeroTactic_NoHero()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'hero',
            'name' => 'Test Hero Action',
            'attributes' => [
                'name' => 'Ancient Dragon',
                'health' => 100,
                'attack' => 50,
                'defense' => 40,
                'evasion' => 20,
                'focus' => 15,
                'counter' => 10,
                'recover' => 25,
                'points_awarded' => 400,
            ],
        ]);

        $data = [];

        // Act & Assert
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('You must have a hero to perform this action');
        
        $this->raidActionService->performAction($this->dominion, $tactic, $data);
    }

    public function testPerformAction_InvasionTactic_Success()
    {
        // Arrange
        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'invasion',
            'name' => 'Assault the Fortress',
            'attributes' => [
                'casualties' => 10.0,
            ],
        ]);

        $data = [
            'unit' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 100, // Only send unit 4 which typically has OP for Human race
            ],
        ];

        // Act
        $result = $this->raidActionService->performAction($this->dominion, $tactic, $data);

        // Assert
        $this->dominion->refresh();
        
        // Verify contribution was recorded with damage-based score
        $contribution = RaidContribution::where('dominion_id', $this->dominion->id)->first();
        $this->assertNotNull($contribution);
        $this->assertEquals('invasion', $contribution->type);
        $this->assertGreaterThan(0, $contribution->score); // Score is calculated dynamically based on damage

        $this->assertStringContainsString('successfully completed', $result['message']);
        $this->assertArrayHasKey('redirect', $result);
    }

    public function testPerformAction_InvalidTacticType()
    {
        // Arrange
        $this->expectException(\ErrorException::class);
        $this->expectExceptionMessage('Trying to access array offset on value of type null');
        
        // Create a tactic with invalid attributes
        $invalidTactic = new RaidObjectiveTactic();
        $invalidTactic->type = 'magic';
        $invalidTactic->attributes = null; // This will cause issues when accessed
        $invalidTactic->objective = $this->objective;
        
        $this->raidActionService->performAction($this->dominion, $invalidTactic, []);
    }

    public function testPerformAction_InactiveObjective()
    {
        // Arrange
        $this->objective->start_date = now()->addDay(); // Future start
        $this->objective->save();

        $tactic = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'espionage',
            'name' => 'Test Operation',
            'attributes' => [
                'strength_cost' => 20,
                'points_awarded' => 100,
            ],
        ]);

        $data = [];

        // Act & Assert
        $this->expectException(GameException::class);
        $this->expectExceptionMessage('This raid objective is not currently active');
        
        $this->raidActionService->performAction($this->dominion, $tactic, $data);
    }

    public function testPerformAction_MultipleContributionsAccumulate()
    {
        // Arrange
        $tactic1 = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'investment',
            'name' => 'Fund Campaign - First (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 3000,
                'points_awarded' => 300,
            ],
        ]);

        $tactic2 = RaidObjectiveTactic::create([
            'raid_objective_id' => $this->objective->id,
            'type' => 'investment',
            'name' => 'Fund Campaign - Second (Platinum)',
            'attributes' => [
                'resource' => 'platinum',
                'amount' => 2000,
                'points_awarded' => 200,
            ],
        ]);

        // Act: Make two separate contributions
        $this->raidActionService->performAction($this->dominion, $tactic1, []);
        $this->raidActionService->performAction($this->dominion, $tactic2, []);

        // Assert: Total contributions should be accumulated
        $totalContributions = RaidContribution::where('dominion_id', $this->dominion->id)->sum('score');
        $this->assertEquals(500, $totalContributions);

        // Verify total resources spent
        $this->dominion->refresh();
        $this->assertEquals(95000, $this->dominion->resource_platinum); // 100000 - 3000 - 2000

        // Verify both contributions were recorded separately
        $contributions = RaidContribution::where('dominion_id', $this->dominion->id)->get();
        $this->assertCount(2, $contributions);
        $this->assertEquals(300, $contributions->where('score', 300)->first()->score);
        $this->assertEquals(200, $contributions->where('score', 200)->first()->score);
        
        // Clear local references to help with memory cleanup
        $tactic1 = null;
        $tactic2 = null;
        $contributions = null;
    }
}
