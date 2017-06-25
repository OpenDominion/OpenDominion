<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class LandCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculatorMock;

    /** @var QueueService */
    protected $dominionQueueServiceMock;

    /** @var LandCalculator */
    protected $landCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->buildingCalculatorMock = m::mock(BuildingCalculator::class);
        $this->buildingCalculatorMock->shouldReceive('setDominion')->with($this->dominionMock);
        $this->app->instance(BuildingCalculator::class, $this->buildingCalculatorMock);

        $this->dominionQueueServiceMock = m::mock(QueueService::class);
        $this->dominionQueueServiceMock->shouldReceive('setDominion')->with($this->dominionMock);
        $this->app->instance(QueueService::class, $this->dominionQueueServiceMock);

        $this->landCalculator = $this->app->make(LandCalculator::class);
        $this->landCalculator->initDependencies();
        $this->landCalculator->init($this->dominionMock);
    }

    public function testGetTotalLand()
    {
        $landTypes = [
            'plain',
            'mountain',
            'swamp',
            'cavern',
            'forest',
            'hill',
            'water',
        ];

        $expected = 0;

        for ($i = 0, $countLandTypes = count($landTypes); $i < $countLandTypes; ++$i) {
            $this->dominionMock->shouldReceive('getAttribute')->with("land_{$landTypes[$i]}")->andReturn(1 << $i);
            $expected += (1 << $i);
        }

        $this->assertEquals($expected, $this->landCalculator->getTotalLand());
    }

    public function testGetTotalBarrenLand()
    {
        $this->dominionMock->shouldReceive('getAttribute')->with('land_plain')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_mountain')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_swamp')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_cavern')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_forest')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_hill')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('land_water')->andReturn(10);

        $this->buildingCalculatorMock->shouldReceive('getTotalBuildings')->with($this->dominionMock)->andReturn(1);

        $this->dominionQueueServiceMock->shouldReceive('getConstructionQueueTotal')->andReturn(2);

        $this->assertEquals(67, $this->landCalculator->getTotalBarrenLand());
    }

    public function testGetTotalBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }

    public function testGetBarrenLandByLandType()
    {
        $raceMock = m::mock(Race::class);
        $raceMock->shouldReceive('getAttribute')->with('home_land_type')->andReturn('plain');

        $this->dominionMock->shouldReceive('getAttribute')->with('race')->andReturn($raceMock);

        $buildingTypesByLandType = [
            'plain' => [
                'home',
                'alchemy',
                'farm',
                'smithy',
                'masonry',
            ],
            'mountain' => [
                'ore_mine',
                'gryphon_nest',
            ],
            'swamp' => [
                'tower',
                'wizard_guild',
                'temple',
            ],
            'cavern' => [
                'diamond_mine',
                'school',
            ],
            'forest' => [
                'lumberyard',
                'forest_haven',
            ],
            'hill' => [
                'factory',
                'guard_tower',
                'shrine',
                'barracks',
            ],
            'water' => [
                'dock',
            ],
        ];

        $expected = array_combine(array_keys($buildingTypesByLandType), array_fill(0, count($buildingTypesByLandType), 0));

        foreach ($buildingTypesByLandType as $landType => $buildingTypes) {
            $this->dominionMock->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(100);
            $expected[$landType] += 100;

            foreach ($buildingTypes as $buildingType) {
                $this->dominionMock->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(2);
                $this->dominionQueueServiceMock->shouldReceive('getConstructionQueueTotalByBuilding')->with($buildingType)->andReturn(1);
                $expected[$landType] -= 3;
            }
        }

        $this->assertEquals($expected, $this->landCalculator->getBarrenLand());
    }

    public function testGetExplorationPlatinumCost()
    {
        $this->markTestIncomplete();
    }

    public function testGetExplorationDrafteeCost()
    {
        $this->markTestIncomplete();
    }

    public function testGetExplorationMaxAfford()
    {
        $this->markTestIncomplete();
    }

    public function testGetExplorationMoraleDrop()
    {
        $this->markTestIncomplete();
    }
}
