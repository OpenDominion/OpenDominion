<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class BuildingCalculatorTest extends AbstractBrowserKitTestCase
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
        $this->landCalculatorDependencyMock->shouldReceive('setDominion')->with($this->dominionMock);
        app()->instance(LandCalculator::class, $this->landCalculatorDependencyMock);

        $this->buildingCalculatorTestMock = m::mock(BuildingCalculator::class)->makePartial();
        $this->buildingCalculatorTestMock->initDependencies();
        $this->buildingCalculatorTestMock->init($this->dominionMock);
    }

    public function testGetTotalBuildings()
    {
        $buildingTypes = [
            'home',
            'alchemy',
            'farm',
            'smithy',
            'masonry',
            'ore_mine',
            'gryphon_nest',
            'tower',
            'wizard_guild',
            'temple',
            'diamond_mine',
            'school',
            'lumberyard',
            'forest_haven',
            'factory',
            'guard_tower',
            'shrine',
            'barracks',
            'dock',
        ];

        $expected = 0;

        foreach ($buildingTypes as $buildingType) {
            $this->dominionMock->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->buildingCalculatorTestMock->getTotalBuildings());
    }

    public function testGetConstructionPlatinumCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedPlatinumCost' => 850],
            ['totalBuildings' => 1250, 'totalLand' => 1250, 'expectedPlatinumCost' => 2380],
        ];

        foreach ($scenarios as $scenario) {
            $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedPlatinumCost'],
                $this->buildingCalculatorTestMock->getConstructionPlatinumCost());
        }
    }

    public function testGetConstructionLumberCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedLumberCost' => 88],
            ['totalBuildings' => 1250, 'totalLand' => 1250, 'expectedLumberCost' => 688],
        ];

        foreach ($scenarios as $scenario) {
            $this->buildingCalculatorTestMock->shouldReceive('getTotalBuildings')->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculatorDependencyMock->shouldReceive('getTotalLand')->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedLumberCost'],
                $this->buildingCalculatorTestMock->getConstructionLumberCost());
        }
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
