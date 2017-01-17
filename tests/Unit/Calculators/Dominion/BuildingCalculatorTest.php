<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\BaseTestCase;

class BuildingCalculatorTest extends BaseTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->buildingCalculator = $this->app->make(BuildingCalculator::class);
        $this->buildingCalculator->init($this->dominionMock);
    }

    public function testGetTotalBuildings()
    {
        $buildingTypes = [
            'home',
            'alchemy',
            'farm',
            'lumberyard',
            'barracks',
        ];

        $expected = 0;

        for ($i = 0, $countBuildingTypes = count($buildingTypes); $i < $countBuildingTypes; ++$i) {
            $this->dominionMock->shouldReceive('getAttribute')->with("building_{$buildingTypes[$i]}")->andReturn(1 << $i);
            $expected += (1 << $i);
        }

        $this->assertEquals($expected, $this->buildingCalculator->getTotalBuildings());
    }

    public function testGetConstructionPlatinumCost()
    {
        $landCalculator = m::mock(LandCalculator::class);
        $landCalculator->shouldReceive('setDominion')->andReturn($landCalculator);
        app()->instance(LandCalculator::class, $landCalculator);

        $this->buildingCalculator = m::mock(BuildingCalculator::class)->makePartial();
        $this->buildingCalculator->init($this->dominionMock);

        // Test with 90 buildings, 250 land
        $this->buildingCalculator->shouldReceive('getTotalBuildings')->andReturn(90)->byDefault();
        $landCalculator->shouldReceive('getTotalLand')->andReturn(250)->byDefault();

        $this->assertEquals(850, $this->buildingCalculator->getConstructionPlatinumCost());

        // Test with 1250 buildings, 1250 land
        $this->buildingCalculator->shouldReceive('getTotalBuildings')->andReturn(1250)->byDefault();
        $landCalculator->shouldReceive('getTotalLand')->andReturn(1250)->byDefault();

        $this->assertEquals(2380, $this->buildingCalculator->getConstructionPlatinumCost());
    }

    public function testGetConstructionLumberCost()
    {
        $this->markTestIncomplete();
    }

    public function testGetConstructionMaxAfford()
    {
        $this->markTestIncomplete();
    }
}
