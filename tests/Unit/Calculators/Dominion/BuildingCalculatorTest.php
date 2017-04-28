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
        $scenarios = [
            [
                'platinum' => 100000,
                'lumber' => 15000,
                'platinumCost' => 850,
                'lumberCost' => 88,
                'expectedMaxAfford' => 117
            ],
            [
                'platinum' => 1000000,
                'lumber' => 150000,
                'platinumCost' => 2380,
                'lumberCost' => 688,
                'expectedMaxAfford' => 218
            ],
        ];

        foreach ($scenarios as $scenario) {
            $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn($scenario['platinum'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn($scenario['lumber'])->byDefault();
            $this->buildingCalculatorTestMock->shouldReceive('getConstructionPlatinumCost')->andReturn($scenario['platinumCost'])->byDefault();
            $this->buildingCalculatorTestMock->shouldReceive('getConstructionLumberCost')->andReturn($scenario['lumberCost'])->byDefault();

            $this->assertEquals($scenario['expectedMaxAfford'],
                $this->buildingCalculatorTestMock->getConstructionMaxAfford());
        }
    }
}
