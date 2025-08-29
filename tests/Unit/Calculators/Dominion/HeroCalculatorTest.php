<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HeroCalculatorTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-3 days midnight');
        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Human')->firstOrFail());

        $this->heroCalculator = $this->app->make(HeroCalculator::class);
    }

    public function testGetPassiveBonus_ActiveClass_FullBonus()
    {
        // Arrange - Create hero with alchemist class at level 5
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1500, // Level 5
            'class_data' => []
        ]);

        // Act - Get bonus for active class (alchemist = platinum_production)
        $bonus = $this->heroCalculator->getPassiveBonus($hero, 'platinum_production');

        // Assert - Should get full bonus (level 5 * coefficient)
        // Alchemist coefficient is 0.2, so level 5 = 1.0%
        $this->assertGreaterThan(0, $bonus);
        $this->assertEquals(1.0, $bonus, 'Active class should get full bonus', 0.01);
    }

    public function testGetPassiveBonus_InactiveClass_HalfBonus()
    {
        // Arrange - Create hero with previous alchemist experience, now blacksmith
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'blacksmith',
            'experience' => 500, // Current class level
            'class_data' => [
                'alchemist' => [
                    'key' => 'alchemist',
                    'experience' => 1500, // Level 5
                    'perk_type' => 'platinum_production'
                ]
            ]
        ]);

        // Act - Get bonus for inactive class (alchemist)
        $bonus = $this->heroCalculator->getPassiveBonus($hero, 'platinum_production');

        // Assert - Should get half bonus (level 5 * coefficient * 0.5)
        // Alchemist coefficient is 0.2, so level 5 * 0.5 = 0.5%
        $this->assertGreaterThan(0, $bonus);
        $this->assertEquals(0.5, $bonus, 'Inactive class should get half bonus', 0.01);
    }

    public function testGetPassiveBonus_NoExperienceInClass_ZeroBonus()
    {
        // Arrange - Create hero with no experience in target class
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'blacksmith',
            'experience' => 1500,
            'class_data' => []
        ]);

        // Act - Get bonus for class hero never had (alchemist = platinum_production)
        $bonus = $this->heroCalculator->getPassiveBonus($hero, 'platinum_production');

        // Assert - Should get zero bonus
        $this->assertEquals(0, $bonus);
    }

    public function testGetPassiveBonus_MultipleInactiveClasses_CorrectBonuses()
    {
        // Arrange - Create hero with experience in multiple classes
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'engineer', // Current class
            'experience' => 1000, // Level 4
            'class_data' => [
                'alchemist' => [
                    'key' => 'alchemist',
                    'experience' => 1500, // Level 5
                    'perk_type' => 'platinum_production'
                ],
                'farmer' => [
                    'key' => 'farmer',
                    'experience' => 600, // Level 3
                    'perk_type' => 'food_production'
                ]
            ]
        ]);

        // Act & Assert - Test active class (full bonus)
        $engineerBonus = $this->heroCalculator->getPassiveBonus($hero, 'invest_bonus');
        $this->assertGreaterThan(0, $engineerBonus);
        // Level 4 engineer (coefficient 0.75) = 3.0%
        $this->assertEquals(3.0, $engineerBonus, 'Active engineer should get full bonus', 0.01);

        // Act & Assert - Test inactive alchemist (half bonus)
        $alchemistBonus = $this->heroCalculator->getPassiveBonus($hero, 'platinum_production');
        $this->assertGreaterThan(0, $alchemistBonus);
        // Level 5 alchemist (coefficient 0.2) * 0.5 = 0.5%
        $this->assertEquals(0.5, $alchemistBonus, 'Inactive alchemist should get half bonus', 0.01);

        // Act & Assert - Test inactive farmer (half bonus)
        $farmerBonus = $this->heroCalculator->getPassiveBonus($hero, 'food_production');
        $this->assertGreaterThan(0, $farmerBonus);
        // Level 3 farmer (coefficient 1.5) * 0.5 = 2.25%
        $this->assertEquals(2.25, $farmerBonus, 'Inactive farmer should get half bonus', 0.01);
    }

    public function testGetHeroPerkMultiplier_CombinesActiveAndInactiveBonuses()
    {
        // Arrange - Create hero with alchemist class
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist', // Active class
            'experience' => 1000, // Level 4
            'class_data' => []
        ]);

        // Act - Get the total perk multiplier for platinum production
        $multiplier = $this->heroCalculator->getHeroPerkMultiplier($this->dominion, 'platinum_production');

        // Assert - Should include active bonus plus any shrine/racial bonuses
        $this->assertGreaterThan(0, $multiplier);

        // The base bonus should be level 4 * 0.2% = 0.8%, converted to multiplier = 0.008
        // Plus potential shrine bonuses (at least 100% base)
        $this->assertGreaterThanOrEqual(0.008, $multiplier);
    }

    public function testCalculatePassiveBonus_DifferentLevels()
    {
        // Test that passive bonus scales correctly with level using alchemist (coefficient 0.2)
        $testCases = [
            ['level' => 0, 'expected' => 0],
            ['level' => 1, 'expected' => 0.2],
            ['level' => 5, 'expected' => 1.0],
            ['level' => 10, 'expected' => 2.0],
        ];

        foreach ($testCases as $testCase) {
            $bonus = $this->heroCalculator->calculatePassiveBonus('platinum_production', $testCase['level']);
            $this->assertEquals($testCase['expected'], $bonus, "Level {$testCase['level']} should give {$testCase['expected']}% bonus");
        }
    }

    public function testGetExperienceLevel_CorrectLevelCalculation()
    {
        $testCases = [
            ['xp' => 0, 'expectedLevel' => 0],
            ['xp' => 50, 'expectedLevel' => 0],
            ['xp' => 100, 'expectedLevel' => 1],
            ['xp' => 299, 'expectedLevel' => 1],
            ['xp' => 300, 'expectedLevel' => 2],
            ['xp' => 1500, 'expectedLevel' => 5],
            ['xp' => 1750, 'expectedLevel' => 5], // Between level 5 and 6
            ['xp' => 2250, 'expectedLevel' => 6],
            ['xp' => 10000, 'expectedLevel' => 12],
            ['xp' => 15000, 'expectedLevel' => 12], // Beyond max level
        ];

        foreach ($testCases as $testCase) {
            $level = $this->heroCalculator->getExperienceLevel($testCase['xp']);
            $this->assertEquals($testCase['expectedLevel'], $level, "XP {$testCase['xp']} should be level {$testCase['expectedLevel']}");
        }
    }

    public function testGetCurrentLevelXP_ReturnsCorrectMinimum()
    {
        $testCases = [
            ['xp' => 50, 'expectedMinimum' => 0],    // Level 0
            ['xp' => 150, 'expectedMinimum' => 100], // Level 1
            ['xp' => 1250, 'expectedMinimum' => 1000], // Level 4
            ['xp' => 1750, 'expectedMinimum' => 1500], // Level 5
            ['xp' => 15000, 'expectedMinimum' => 10000], // Level 12 (max)
        ];

        foreach ($testCases as $testCase) {
            // Arrange
            $hero = Hero::create([
                'dominion_id' => $this->dominion->id,
                'name' => 'Test Hero',
                'class' => 'alchemist',
                'experience' => $testCase['xp'],
                'class_data' => []
            ]);

            // Act
            $minimumXp = $this->heroCalculator->getCurrentLevelXP($hero);

            // Assert
            $this->assertEquals(
                $testCase['expectedMinimum'],
                $minimumXp,
                "Hero with {$testCase['xp']} XP should have minimum of {$testCase['expectedMinimum']} for current level"
            );
        }
    }

    public function testGetNextLevelXP_ReturnsCorrectNextLevel()
    {
        $testCases = [
            ['xp' => 50, 'expectedNext' => 100],   // Level 0 -> 1
            ['xp' => 150, 'expectedNext' => 300],  // Level 1 -> 2
            ['xp' => 1250, 'expectedNext' => 1500], // Level 4 -> 5
            ['xp' => 1750, 'expectedNext' => 2250], // Level 5 -> 6
            ['xp' => 15000, 'expectedNext' => 99999], // Level 12 (max) -> beyond
        ];

        foreach ($testCases as $testCase) {
            // Arrange
            $hero = Hero::create([
                'dominion_id' => $this->dominion->id,
                'name' => 'Test Hero',
                'class' => 'alchemist',
                'experience' => $testCase['xp'],
                'class_data' => []
            ]);

            // Act
            $nextLevelXp = $this->heroCalculator->getNextLevelXP($hero);

            // Assert
            $this->assertEquals(
                $testCase['expectedNext'],
                $nextLevelXp,
                "Hero with {$testCase['xp']} XP should need {$testCase['expectedNext']} XP for next level"
            );
        }
    }
}
