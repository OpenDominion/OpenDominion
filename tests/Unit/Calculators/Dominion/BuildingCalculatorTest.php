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

    /** @var LandCalculator */
    protected $landCalculatorDependencyMock;

    /** @var BuildingCalculator */
    protected $buildingCalculatorTestMock;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->landCalculatorDependencyMock = m::mock(LandCalculator::class);
        $this->landCalculatorDependencyMock->shouldReceive('setDominion')->andReturn($this->landCalculatorDependencyMock);
        app()->instance(LandCalculator::class, $this->landCalculatorDependencyMock);

        $this->buildingCalculatorTestMock = m::mock(BuildingCalculator::class)->makePartial();
        $this->buildingCalculatorTestMock->init($this->dominionMock);
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

        $this->assertEquals($expected, $this->buildingCalculatorTestMock->getTotalBuildings());
    }

    public function testGetConstructionPlatinumCost()
    {
        // Starting values
        $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn(90)->byDefault();
        $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn(250)->byDefault();

        $this->assertEquals(850, $this->buildingCalculatorTestMock->getConstructionPlatinumCost());

        // Test with 1250 buildings, 1250 land
        $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn(1250)->byDefault();
        $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn(1250)->byDefault();

        $this->assertEquals(2380, $this->buildingCalculatorTestMock->getConstructionPlatinumCost());
    }

    public function testGetConstructionLumberCost()
    {
        // Starting values
        $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn(90)->byDefault();
        $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn(250)->byDefault();

        $this->assertEquals(88, $this->buildingCalculatorTestMock->getConstructionLumberCost());

        // Test with 1250 buildings, 1250 land
        $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn(1250)->byDefault();
        $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn(1250)->byDefault();

        $this->assertEquals(688, $this->buildingCalculatorTestMock->getConstructionLumberCost());
    }

    public function testGetConstructionMaxAfford()
    {
        // Starting values
        $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn(100000);
        $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn(15000);
        $this->buildingCalculatorTestMock->shouldReceive('getConstructionPlatinumCost')->andReturn(850);
        $this->buildingCalculatorTestMock->shouldReceive('getConstructionLumberCost')->andReturn(88);

        $this->assertEquals(117, $this->buildingCalculatorTestMock->getConstructionMaxAfford());
    }
}
