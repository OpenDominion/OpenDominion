<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class LandCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var QueueService */
    protected $dominionQueueService;

    /** @var LandCalculator */
    protected $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);
        $this->buildingCalculator = m::mock(BuildingCalculator::class);
        $this->dominionQueueService = m::mock(QueueService::class);

        $this->sut = m::mock(LandCalculator::class, [
            $this->buildingCalculator,
            $this->app->make(BuildingHelper::class),
            $this->app->make(LandHelper::class),
            $this->dominionQueueService,
        ])->makePartial();
    }

    public function testGetTotalLand()
    {
        $expected = 0;

        foreach ($this->getLandTypes() as $landType) {
            $this->dominionMock->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalLand($this->dominionMock));
    }

    public function testGetTotalBarrenLand()
    {
        foreach ($this->getLandTypes() as $landType) {
            $this->dominionMock->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(10);
        }

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->andReturn(1);
        $this->dominionQueueService->shouldReceive('getConstructionQueueTotal')->with($this->dominionMock)->andReturn(2);

        $this->assertEquals(67, $this->sut->getTotalBarrenLand($this->dominionMock));
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
                $this->dominionQueueService->shouldReceive('getConstructionQueueTotalByBuilding')->with($this->dominionMock, $buildingType)->andReturn(1);
                $expected[$landType] -= 3;
            }
        }

        $this->assertEquals($expected, $this->sut->getBarrenLand($this->dominionMock));
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

    private function getLandTypes()
    {
        return [
            'plain',
            'mountain',
            'swamp',
            'cavern',
            'forest',
            'hill',
            'water',
        ];
    }
}
