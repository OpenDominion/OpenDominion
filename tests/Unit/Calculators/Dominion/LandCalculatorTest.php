<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\LandCalculator
 */
class LandCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|ConstructionQueueService */
    protected $constructionQueueService;

    /** @var Mock|LandCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(LandCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->app->make(BuildingHelper::class),
            $this->constructionQueueService = m::mock(ConstructionQueueService::class),
            $this->app->make(LandHelper::class),
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(LandCalculator::class, $this->app->make(LandCalculator::class));
    }

    public function testGetTotalLand()
    {
        $expected = 0;

        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalLand($this->dominion));
    }

    public function testGetTotalBarrenLand()
    {
        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(10);
        }

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominion)->andReturn(1);
        $this->constructionQueueService->shouldReceive('getQueueTotal')->with($this->dominion)->andReturn(2);

        $this->assertEquals(67, $this->sut->getTotalBarrenLand($this->dominion));
    }

    public function testGetTotalBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }

    public function testGetBarrenLandByLandType()
    {
        $raceMock = m::mock(Race::class);
        $raceMock->shouldReceive('getAttribute')->with('home_land_type')->andReturn('plain');

        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($raceMock);

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
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(100);
            $expected[$landType] += 100;

            foreach ($buildingTypes as $buildingType) {
                $this->dominion->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(2);
                $this->constructionQueueService->shouldReceive('getQueueTotalByBuilding')->with($this->dominion, $buildingType)->andReturn(1);
                $expected[$landType] -= 3;
            }
        }

        $this->assertEquals($expected, $this->sut->getBarrenLandByLandType($this->dominion));
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
