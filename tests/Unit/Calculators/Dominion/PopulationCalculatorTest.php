<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\PopulationCalculator
 */
class PopulationCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|ImprovementCalculator */
    protected $improvementsCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|MilitaryCalculator */
    protected $militaryCalculator;

    /** @var Mock|PrestigeCalculator */
    protected $prestigeCalculator;

    /** @var Mock|QueueService */
    protected $queueService;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|PopulationCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(PopulationCalculator::class, [
            $this->app->make(BuildingHelper::class),
            $this->improvementsCalculator = m::mock(ImprovementCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->militaryCalculator = m::mock(MilitaryCalculator::class),
            $this->prestigeCalculator = m::mock(PrestigeCalculator::class),
            $this->queueService = m::mock(QueueService::class),
            $this->spellCalculator = m::mock(SpellCalculator::class),
            $this->app->make(UnitHelper::class)
        ])->makePartial();
    }

    public function testGetPopulation()
    {
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1000);
        $this->sut->shouldReceive('getPopulationMilitary')->andReturn(500);

        $result = $this->sut->getPopulation($this->dominion);

        $this->assertEquals(1500, $result);
    }

    public function testGetPopulationMilitary()
    {
        $this->dominion->shouldReceive('getAttribute')->with('military_draftees')->andReturn(100);
        $this->dominion->shouldReceive('getAttribute')->with('military_spies')->andReturn(50);
        $this->dominion->shouldReceive('getAttribute')->with('military_assassins')->andReturn(25);
        $this->dominion->shouldReceive('getAttribute')->with('military_wizards')->andReturn(75);
        $this->dominion->shouldReceive('getAttribute')->with('military_archmages')->andReturn(30);

        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($this->dominion, 1)->andReturn(200);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($this->dominion, 2)->andReturn(150);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($this->dominion, 3)->andReturn(100);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($this->dominion, 4)->andReturn(80);

        $this->queueService->shouldReceive('getTrainingQueueTotal')->with($this->dominion)->andReturn(90);

        $result = $this->sut->getPopulationMilitary($this->dominion);

        $this->assertEquals(900, $result); // 100+50+25+75+30+200+150+100+80+90
    }

    public function testGetMaxPopulation()
    {
        $this->sut->shouldReceive('getMaxPopulationRaw')->andReturn(5000);
        $this->sut->shouldReceive('getMaxPopulationMultiplier')->andReturn(1.15);
        $this->sut->shouldReceive('getMaxPopulationMilitaryBonus')->andReturn(200);

        $result = $this->sut->getMaxPopulation($this->dominion);

        $this->assertEquals(5950, $result); // round(5000 * 1.15) + 200
    }

    public function testGetMaxPopulationRaw()
    {
        // Mock dominion race methods
        $mockRace = m::mock();
        $mockRace->shouldReceive('getPerkValue')->with('extra_barren_max_population')->andReturn(2);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($mockRace);

        // Mock dominion tech/wonder methods
        $this->dominion->shouldReceive('getTechPerkValue')->with('extra_barren_max_population')->andReturn(1);
        $this->dominion->shouldReceive('getWonderPerkValue')->with('extra_barren_max_population')->andReturn(0);

        // Mock building attributes
        $this->dominion->shouldReceive('getAttribute')->with('building_home')->andReturn(150);
        $this->dominion->shouldReceive('getAttribute')->with('building_barracks')->andReturn(50);
        $this->dominion->shouldReceive('getAttribute')->with('building_alchemy')->andReturn(25);
        $this->dominion->shouldReceive('getAttribute')->with('building_farm')->andReturn(75);
        $this->dominion->shouldReceive('getAttribute')->with('building_smithy')->andReturn(0);
        $this->dominion->shouldReceive('getAttribute')->with('building_masonry')->andReturn(0);
        // Mock other building types as 0 for simplicity
        foreach (['ore_mine', 'gryphon_nest', 'tower', 'wizard_guild', 'temple', 'diamond_mine',
                  'school', 'lumberyard', 'forest_haven', 'guard_tower', 'shrine', 'dock', 'factory'] as $building) {
            $this->dominion->shouldReceive('getAttribute')->with("building_{$building}")->andReturn(0);
        }

        $this->queueService->shouldReceive('getConstructionQueueTotal')->with($this->dominion)->andReturn(10);
        $this->landCalculator->shouldReceive('getTotalBarrenLand')->with($this->dominion)->andReturn(100);

        $result = $this->sut->getMaxPopulationRaw($this->dominion);

        // Expected calculation:
        // homes: 150 * 30 = 4500
        // barracks: 50 * 0 = 0
        // other buildings: (25 + 75) * 15 = 1500
        // constructing: 10 * 15 = 150
        // barren land: 100 * (5 + 2 + 1 + 0) = 800
        // Total: 4500 + 0 + 1500 + 150 + 800 = 6950
        $this->assertEquals(6950, $result);
    }

    public function testGetMaxPopulationMultiplier()
    {
        // Mock dominion race methods
        $mockRace = m::mock();
        $mockRace->shouldReceive('getPerkMultiplier')->with('max_population')->andReturn(0.10);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($mockRace);

        // Mock dominion tech/wonder methods
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('max_population')->andReturn(0.05);
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('max_population')->andReturn(0.03);

        // Mock improvement calculator
        $this->improvementsCalculator->shouldReceive('getImprovementMultiplierBonus')
            ->with($this->dominion, 'keep')->andReturn(0.08);

        // Mock prestige calculator
        $this->prestigeCalculator->shouldReceive('getPrestigeMultiplier')->with($this->dominion)
            ->andReturn(0.02);

        $result = $this->sut->getMaxPopulationMultiplier($this->dominion);

        // Expected calculation: (1 + 0.10 + 0.05 + 0.03 + 0.08) * (1 + 0.02) = 1.26 * 1.02 = 1.2852
        $this->assertEqualsWithDelta(1.2852, $result, 0.0001); // Use delta for floating point comparison
    }

    public function testGetPopulationBirth()
    {
        $this->sut->shouldReceive('getPopulationBirthRaw')->andReturn(45.0);
        $this->sut->shouldReceive('getPopulationBirthMultiplier')->andReturn(1.25);

        $result = $this->sut->getPopulationBirth($this->dominion);

        $this->assertEquals(56, $result); // round(45.0 * 1.25)
    }

    public function testGetPopulationBirthRaw()
    {
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1500);
        $this->sut->shouldReceive('getPopulationDrafteeGrowth')->andReturn(15);

        $result = $this->sut->getPopulationBirthRaw($this->dominion);

        // Expected calculation: (1500 - 15) * (3 / 100) = 1485 * 0.03 = 44.55
        $this->assertEquals(44.55, $result);
    }

    public function testGetPopulationBirthMultiplier()
    {
        // Test with food - should return multiplier > 0
        $this->dominion->shouldReceive('getAttribute')->with('resource_food')->andReturn(1000);

        // Mock dominion race methods
        $mockRace = m::mock();
        $mockRace->shouldReceive('getPerkMultiplier')->with('population_growth')->andReturn(0.05);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($mockRace);

        // Mock dominion tech methods
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('population_growth')->andReturn(0.03);

        // Mock spell calculator
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'population_growth')->andReturn(10); // 10%

        // Mock building temple and land
        $this->dominion->shouldReceive('getAttribute')->with('building_temple')->andReturn(30);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominion)->andReturn(500);

        $result = $this->sut->getPopulationBirthMultiplier($this->dominion);

        // Expected calculation: 1 + 0.05 + 0.03 + (10/100) + ((30/500) * 6) = 1 + 0.05 + 0.03 + 0.10 + 0.36 = 1.54
        $this->assertEquals(1.54, $result);
    }

    public function testGetPopulationBirthMultiplierWithStarvation()
    {
        // Test with no food - should return 0 (starvation)
        $this->dominion->shouldReceive('getAttribute')->with('resource_food')->andReturn(0);

        $result = $this->sut->getPopulationBirthMultiplier($this->dominion);

        $this->assertEquals(0, $result);
    }

    /**
     * @dataProvider getPopulationPeasantGrowthProvider
     */
    public function testGetPopulationPeasantGrowth(
        /** @noinspection PhpDocSignatureInspection */
        int $expected,
        int $peasants,
        int $drafteeGrowth,
        int $maxPopulation,
        int $population,
        int $populationBirth
    ) {
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn($peasants);
        $this->sut->shouldReceive('getPopulationDrafteeGrowth')->andReturn($drafteeGrowth);
        $this->sut->shouldReceive('getMaxPopulation')->andReturn($maxPopulation);
        $this->sut->shouldReceive('getPopulation')->andReturn($population);
        $this->sut->shouldReceive('getPopulationBirth')->andReturn($populationBirth);

        $this->assertEquals(
            $expected,
            $this->sut->getPopulationPeasantGrowth($this->dominion),
            sprintf(
                "Population: %s/%s\nBirth: %s\nDraftee Growth: %s",
                $population,
                $maxPopulation,
                $populationBirth,
                $drafteeGrowth
            )
        );
    }

    public function getPopulationPeasantGrowthProvider()
    {
        return [
            [39, 1300, 0, 2358, 1600, 39],
            [64, 1853, 0, 2563, 2449, 64],
        ];
    }

    public function testGetPopulationDrafteeGrowth()
    {
        // Test case 1: Military percentage below draft rate - should draft
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1000);
        $this->dominion->shouldReceive('getAttribute')->with('draft_rate')->andReturn(30.0);
        $this->sut->shouldReceive('getPopulationMilitaryPercentage')->with($this->dominion)->andReturn(25.0);

        $result = $this->sut->getPopulationDrafteeGrowth($this->dominion);
        $this->assertEquals(10, $result); // round(1000 * 0.01) = 10
    }

    public function testGetPopulationDrafteeGrowthNoDrafting()
    {
        // Test case 2: Military percentage at or above draft rate - no drafting
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1000);
        $this->dominion->shouldReceive('getAttribute')->with('draft_rate')->andReturn(30.0);
        $this->sut->shouldReceive('getPopulationMilitaryPercentage')->with($this->dominion)->andReturn(35.0);

        $result = $this->sut->getPopulationDrafteeGrowth($this->dominion);
        $this->assertEquals(0, $result);
    }

    public function testGetPopulationPeasantPercentage()
    {
        // Test case 1: Normal case
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(800);
        $this->sut->shouldReceive('getPopulation')->with($this->dominion)->andReturn(1200);

        $result = $this->sut->getPopulationPeasantPercentage($this->dominion);
        $this->assertEquals(66.67, round($result, 2)); // (800/1200) * 100 = 66.67%
    }

    public function testGetPopulationPeasantPercentageZeroPopulation()
    {
        // Test case 2: Zero population
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(0);
        $this->sut->shouldReceive('getPopulation')->with($this->dominion)->andReturn(0);

        $result = $this->sut->getPopulationPeasantPercentage($this->dominion);
        $this->assertEquals(0.0, $result);
    }

    public function testGetEmploymentJobs()
    {
        // Mock all building attributes
        $buildings = [
            'alchemy', 'farm', 'smithy', 'masonry', 'ore_mine', 'gryphon_nest', 'tower',
            'wizard_guild', 'temple', 'diamond_mine', 'school', 'lumberyard', 'forest_haven',
            'guard_tower', 'shrine', 'dock'
        ];

        $totalRegularBuildings = 0;
        foreach ($buildings as $building) {
            $count = ($building === 'farm') ? 50 : (($building === 'temple') ? 20 : 0);
            $this->dominion->shouldReceive('getAttribute')->with("building_{$building}")->andReturn($count);
            $totalRegularBuildings += $count;
        }

        // Mock factory buildings (different job count)
        $this->dominion->shouldReceive('getAttribute')->with('building_factory')->andReturn(10);

        // Mock wonder employment multiplier
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('employment')->andReturn(0.05);

        $result = $this->sut->getEmploymentJobs($this->dominion);

        // Expected: (70 * 20) + (10 * 25) = 1400 + 250 = 1650
        // With wonder bonus: 1650 * 1.05 = 1732.5 = 1732 (truncated)
        $this->assertEquals(1732, $result);
    }

    public function testGetPopulationEmployed()
    {
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1500);
        $this->sut->shouldReceive('getEmploymentJobs')->with($this->dominion)->andReturn(1200);

        $result = $this->sut->getPopulationEmployed($this->dominion);

        $this->assertEquals(1200, $result); // min(1200, 1500) = 1200
    }

    public function testGetPopulationEmployedMoreJobs()
    {
        // Test case with more jobs than peasants
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1500);
        $this->sut->shouldReceive('getEmploymentJobs')->with($this->dominion)->andReturn(2000);

        $result = $this->sut->getPopulationEmployed($this->dominion);
        $this->assertEquals(1500, $result); // min(2000, 1500) = 1500
    }

    public function testGetEmploymentPercentage()
    {
        // Test case 1: Normal employment
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1000);
        $this->sut->shouldReceive('getPopulationEmployed')->with($this->dominion)->andReturn(800);

        $result = $this->sut->getEmploymentPercentage($this->dominion);
        $this->assertEquals(80.0, $result); // (800/1000) * 100 = 80%
    }

    public function testGetEmploymentPercentageFullEmployment()
    {
        // Test case 2: Full employment (more jobs than peasants)
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(1000);
        $this->sut->shouldReceive('getPopulationEmployed')->with($this->dominion)->andReturn(1200);

        $result = $this->sut->getEmploymentPercentage($this->dominion);
        $this->assertEquals(100.0, $result); // min(1, (1200/1000)) * 100 = 100%
    }

    public function testGetEmploymentPercentageNoPeasants()
    {
        // Test case 3: No peasants
        $this->dominion->shouldReceive('getAttribute')->with('peasants')->andReturn(0);
        $result = $this->sut->getEmploymentPercentage($this->dominion);
        $this->assertEquals(0, $result);
    }
}
