<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
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
            'experience' => 2000, // Level 4 (min 1750) with 250 excess XP
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level minimum (1750)
        $hero->refresh();
        $this->assertEquals('blacksmith', $hero->class);
        $this->assertEquals(0, $hero->experience); // New class starts at 0
        $this->assertEquals(1750, $hero->class_data['alchemist']['experience']); // Capped at level 4 minimum
    }

    public function testChangeClass_ExactlyAtLevelMinimum_NoXpLoss()
    {
        // Arrange - Create hero with exact XP for level
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 1750, // Exactly level 4 minimum
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - No XP should be lost
        $hero->refresh();
        $this->assertEquals(1750, $hero->class_data['alchemist']['experience']);
    }

    public function testChangeClass_BetweenLevelMinimums_CapsAtCurrentLevelMinimum()
    {
        // Arrange - Create hero with XP between level minimums
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 2000, // Level 4 (1750-2299) - should cap at 1750
            'class_data' => []
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - XP should be capped at level 4 minimum (1750)
        $hero->refresh();
        $this->assertEquals(1750, $hero->class_data['alchemist']['experience']);
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

        // Assert - XP loss is capped at 500 (15000 - 500 = 14500)
        $hero->refresh();
        $this->assertEquals(14500, $hero->class_data['alchemist']['experience']);
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
            'experience' => 2000, // Level 4 (min 1750) with 250 excess XP
            'class_data' => $existingClassData
        ]);

        // Act - Change class
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - Both old classes should be preserved
        $hero->refresh();
        $this->assertEquals(1750, $hero->class_data['alchemist']['experience']); // Capped at level 3 minimum
        $this->assertEquals(800, $hero->class_data['farmer']['experience']); // Preserved
    }

    public function testChangeClass_BeforeRoundStart_SetsLastClassChangeAtToRoundStartDate()
    {
        // Arrange - Create a round that hasn't started yet, and a hero in it
        $futureRound = $this->createRound('+1 day', '+48 days');
        $futureDominion = $this->createDominionWithLegacyStats(
            $this->createUser(),
            $futureRound,
            Race::where('name', 'Human')->firstOrFail()
        );

        $hero = Hero::create([
            'dominion_id' => $futureDominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => []
        ]);

        // Act - Change class before the round starts
        $this->heroActionService->changeClass($futureDominion, 'blacksmith');

        // Assert - last_class_change_at should match round start_date, not now()
        $hero->refresh();
        $this->assertEquals('blacksmith', $hero->class);
        $this->assertEquals(
            $futureRound->start_date->timestamp,
            $hero->last_class_change_at->timestamp
        );
    }

    public function testChangeClass_AfterRoundStart_SetsLastClassChangeAtToNow()
    {
        // Arrange - Round in setUp already started 3 days ago
        $hero = Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => []
        ]);

        // Act
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - last_class_change_at should be ~now, not round start_date
        $hero->refresh();
        $this->assertEqualsWithDelta(now()->timestamp, $hero->last_class_change_at->timestamp, 5);
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
            'experience' => 2000, // Level 6 with excess XP
            'class_data' => $existingClassData
        ]);

        // Act - Change back to blacksmith
        $this->heroActionService->changeClass($this->dominion, 'blacksmith');

        // Assert - Should restore previous blacksmith XP and cap alchemist XP
        $hero->refresh();
        $this->assertEquals('blacksmith', $hero->class);
        $this->assertEquals(2500, $hero->experience); // Restored blacksmith XP
        $this->assertEquals(1750, $hero->class_data['alchemist']['experience']); // Capped alchemist XP at level 4 minimum
    }

    public function testChangeClass_Scion_RequiresLandConqueredNotSuccessfulAttacks()
    {
        $this->arrangeForScion();

        // Plenty of successful attacks no longer qualifies on its own
        $this->dominion->stat_attacking_success = 50;
        $this->dominion->stat_total_land_conquered = 499;
        $this->dominion->save();

        $this->expectException(GameException::class);
        $this->expectExceptionMessage('do not meet the requirements');

        $this->heroActionService->changeClass($this->dominion, 'scion');
    }

    public function testChangeClass_Scion_AllowedAtFiveHundredAcresConquered()
    {
        $this->arrangeForScion();

        // No successful attacks at all, but enough land taken
        $this->dominion->stat_attacking_success = 0;
        $this->dominion->stat_total_land_conquered = 500;
        $this->dominion->save();

        $this->heroActionService->changeClass($this->dominion, 'scion');

        $this->assertEquals('scion', $this->dominion->hero->fresh()->class);
    }

    public function testChangeClass_Scion_NotGatedByRoundDay()
    {
        $this->arrangeForScion();

        // Day 2 of the round; only the acres requirement should matter now
        $this->round->start_date = now()->subDays(1);
        $this->round->save();
        $this->dominion->setRelation('round', $this->round->fresh());

        $this->dominion->stat_total_land_conquered = 500;
        $this->dominion->save();

        $this->heroActionService->changeClass($this->dominion, 'scion');

        $this->assertEquals('scion', $this->dominion->hero->fresh()->class);
    }

    public function testScionRequirementIsDisplayedInAcres()
    {
        $scion = app(HeroHelper::class)->getClasses()->get('scion');

        $this->assertEquals('stat_total_land_conquered', $scion['requirement_stat']);
        $this->assertEquals(500, $scion['requirement_value']);
        $this->assertEquals('500 acres conquered', app(HeroHelper::class)->getRequirementDisplay($scion));
    }

    /**
     * Gives the dominion a hero that is eligible to change class.
     */
    protected function arrangeForScion(): void
    {
        Hero::create([
            'dominion_id' => $this->dominion->id,
            'name' => 'Test Hero',
            'class' => 'alchemist',
            'experience' => 0,
            'class_data' => []
        ]);

        $this->dominion->load('heroes');
    }
}
