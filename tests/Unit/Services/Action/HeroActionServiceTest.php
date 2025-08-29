<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\HeroActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HeroActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var HeroActionService */
    protected $heroActionService;

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

        $this->heroActionService = $this->app->make(HeroActionService::class);
    }

    public function testChangeClass_CapsXpAtCurrentLevelMinimum()
    {
        // Arrange - Create hero with XP beyond level minimum
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1750, // Level 6 (min 1500) with 250 excess XP
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level minimum (1500)
        $hero->refresh();
        $this->assertEquals('blacksmith', $hero->class);
        $this->assertEquals(0, $hero->experience); // New class starts at 0
        $this->assertEquals(1500, $hero->class_data['alchemist']['experience']); // Capped at level 6 minimum
    }

    public function testChangeClass_ExactlyAtLevelMinimum_NoXpLoss()
    {
        // Arrange - Create hero with exact XP for level
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1500, // Exactly level 6 minimum
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - No XP should be lost
        $hero->refresh();
        $this->assertEquals(1500, $hero->class_data['alchemist']['experience']);
    }

    public function testChangeClass_BetweenLevelMinimums_CapsAtCurrentLevelMinimum()
    {
        // Arrange - Create hero with XP between level minimums
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1400, // Level 4 (1000-1499) - should cap at 1000
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level 4 minimum (1000)
        $hero->refresh();
        $this->assertEquals(1000, $hero->class_data['alchemist']['experience']);
    }

    public function testChangeClass_Level0_NoXpLoss()
    {
        // Arrange - Create hero at level 0
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 50, // Level 0 (below 100 XP)
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level 0 minimum (0)
        $hero->refresh();
        $this->assertEquals(0, $hero->class_data['alchemist']['experience']);
    }

    public function testChangeClass_MaxLevel_CapsAtMaxLevelMinimum()
    {
        // Arrange - Create hero at max level with excess XP
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 15000, // Level 12 (min 10000) with 5000 excess XP
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level 12 minimum (10000)
        $hero->refresh();
        $this->assertEquals(10000, $hero->class_data['alchemist']['experience']);
    }

    public function testChangeClass_PreservesExistingClassData()
    {
        // Arrange - Create hero with existing class data
        $existingClassData = [
            'farmer' => [
                'key' => 'farmer',
                'experience' => 800,
                'perk_type' => 'food_production'
            ]
        ];

        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1250, // Level 4 (min 1000) with 250 excess XP
            'class_data' => $existingClassData
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - Both old classes should be preserved
        $hero->refresh();
        $this->assertEquals(1000, $hero->class_data['alchemist']['experience']); // Capped at level 4 minimum
        $this->assertEquals(800, $hero->class_data['farmer']['experience']); // Preserved
    }

    public function testChangeClass_RestoresPreviousClassExperience()
    {
        // Arrange - Create hero with previous blacksmith experience
        $existingClassData = [
            'blacksmith' => [
                'key' => 'blacksmith',
                'experience' => 2500,
                'perk_type' => 'military_cost'
            ]
        ];

        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1750, // Level 6 with excess XP
            'class_data' => $existingClassData
        ]);

        // Act - Change back to blacksmith
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - Should restore previous blacksmith XP and cap alchemist XP
        $hero->refresh();
        $this->assertEquals('blacksmith', $hero->class);
        $this->assertEquals(2500, $hero->experience); // Restored blacksmith XP
        $this->assertEquals(1500, $hero->class_data['alchemist']['experience']); // Capped alchemist XP at level 6 minimum
    }
}
