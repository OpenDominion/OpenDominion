<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ConstructionCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var ConstructionCalculator */
    protected $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);
        $this->buildingCalculator = m::mock(BuildingCalculator::class);
        $this->landCalculator = m::mock(LandCalculator::class);

        $this->sut = m::mock(ConstructionCalculator::class, [
            $this->buildingCalculator,
            $this->landCalculator,
        ])->makePartial();
    }

    public function testGetPlatinumCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedPlatinumCost' => 850],
            ['totalBuildings' => 1250, 'totalLand' => 1250, 'expectedPlatinumCost' => 2380],
        ];

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedPlatinumCost'], $this->sut->getPlatinumCost($this->dominionMock));
        }
    }

    public function testGetLumberCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedLumberCost' => 88],
            ['totalBuildings' => 1250, 'totalLand' => 1250, 'expectedLumberCost' => 688],
        ];

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedLumberCost'], $this->sut->getLumberCost($this->dominionMock));
        }
    }

    public function testGetMaxAfford()
    {
        $scenarios = [
            [ // totalBuildings < 1250
                'totalBuildings' => 90,
                'totalLand' => 250,
                'totalBarrenLand' => 160,
                'platinum' => 100000,
                'lumber' => 15000,
                'expectedMaxAfford' => 117,
            ],
            [ // totalBuildings >= 1250
                'totalBuildings' => 1250,
                'totalLand' => 5000,
                'totalBarrenLand' => 4750,
                'platinum' => 1000000,
                'lumber' => 150000,
                'expectedMaxAfford' => 68,
            ],
            [ // capped by barren land
                'totalBuildings' => 90,
                'totalLand' => 250,
                'totalBarrenLand' => 160,
                'platinum' => 10000000,
                'lumber' => 1500000,
                'expectedMaxAfford' => 160,
            ],
        ];

        foreach ($scenarios as $scenario) {
            $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn($scenario['platinum'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn($scenario['lumber'])->byDefault();

            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalBarrenLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBarrenLand'])->byDefault();

            $this->assertEquals($scenario['expectedMaxAfford'], $this->sut->getMaxAfford($this->dominionMock));
        }
    }
}
