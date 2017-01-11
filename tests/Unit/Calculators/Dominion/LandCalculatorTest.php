<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Tests\BaseTestCase;

class LandCalculatorTest extends BaseTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculatorMock;

    /** @var LandCalculator */
    protected $landCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->buildingCalculatorMock = m::mock(BuildingCalculator::class);

        $this->app->bind(BuildingCalculator::class, function ($app) {
            return $this->buildingCalculatorMock;
        });

        $this->landCalculator = $this->app->make(LandCalculator::class, [$this->dominionMock]);
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

        $this->buildingCalculatorMock->shouldReceive('getTotalBuildings')->andReturn(1);

        // todo: construction queue

        $this->assertEquals(69, $this->landCalculator->getTotalBarrenLand());
    }

    public function testGetBarrenLandByLandType()
    {
        $raceMock = m::mock(Race::class);
        $raceMock->shouldReceive('getAttribute')->with('home_land_type')->andReturn('plain');

        $this->dominionMock->shouldReceive('getAttribute')->with('race')->andReturn($raceMock);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_plain')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('building_home')->andReturn(1);
        $this->dominionMock->shouldReceive('getAttribute')->with('building_alchemy')->andReturn(1);
        $this->dominionMock->shouldReceive('getAttribute')->with('building_farm')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_smithy')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_masonry')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_mountain')->andReturn(10);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_ore_mine')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_gryphon_nest')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_swamp')->andReturn(10);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_tower')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_wizard_guild')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_template')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_cavern')->andReturn(10);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_diamond_mine')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_school')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_forest')->andReturn(10);
        $this->dominionMock->shouldReceive('getAttribute')->with('building_lumberyard')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_forest_haven')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_hill')->andReturn(10);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_factory')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_guard_tower')->andReturn(1);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_shrine')->andReturn(1);
        $this->dominionMock->shouldReceive('getAttribute')->with('building_barracks')->andReturn(1);

        $this->dominionMock->shouldReceive('getAttribute')->with('land_water')->andReturn(10);
//        $this->dominionMock->shouldReceive('getAttribute')->with('building_dock')->andReturn(1);

        // todo: construction queue

        $this->assertEquals([
            'plain' => 7,
            'mountain' => 10,
            'swamp' => 10,
            'cavern' => 10,
            'forest' => 9,
            'hill' => 9,
            'water' => 10,
        ], $this->landCalculator->getBarrenLandByLandType());
    }
}
