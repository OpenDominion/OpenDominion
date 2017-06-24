<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ConstructionCalculator;
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
        $this->buildingCalculator = m::mock(BuildingCalculator::class);//->makePartial();
        $this->landCalculator = m::mock(LandCalculator::class);//->makePartial();

//        $this->sut = $this->app->make(ConstructionCalculator::class);
        $this->sut = $this->app->makeWith(ConstructionCalculator::class, [
            'buildingCalculator' => $this->buildingCalculator,
            'landCalculator' => $this->landCalculator,
        ]);

        // todo
//        $this->buildingCalculator->initDependencies();
//        $this->buildingCalculator->init($this->dominionMock);
//        $this->landCalculator->initDependencies();
//        $this->landCalculator->init($this->dominionMock);
    }

    public function testGetPlatinumCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedPlatinumCost' => 850],
            ['totalBuildings' => 1250, 'totalLand' => 1250, 'expectedPlatinumCost' => 2380],
        ];

        foreach ($scenarios as $scenario) {
            // todo: remove after refactor
            $this->buildingCalculator->shouldReceive('setDominion');
            $this->landCalculator->shouldReceive('setDominion');

            $this->buildingCalculator->shouldReceive('getTotalBuildings')->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->andReturn($scenario['totalLand'])->byDefault();

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
            // todo: remove after refactor
            $this->buildingCalculator->shouldReceive('setDominion');
            $this->landCalculator->shouldReceive('setDominion');

            $this->buildingCalculator->shouldReceive('getTotalBuildings')->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedLumberCost'], $this->sut->getLumberCost($this->dominionMock));
        }
    }

    public function testGetMaxAfford()
    {
        $scenarios = [
            [
                'totalBuildings' => 90,
                'totalLand' => 250,
                'platinum' => 100000,
                'lumber' => 15000,
                'expectedMaxAfford' => 117
            ],
            [
                'totalBuildings' => 1250,
                'totalLand' => 1250,
                'platinum' => 1000000,
                'lumber' => 150000,
                'expectedMaxAfford' => 218
            ],
        ];

        foreach ($scenarios as $scenario) {
            // todo: remove after refactor
            $this->buildingCalculator->shouldReceive('setDominion');
            $this->landCalculator->shouldReceive('setDominion');

            $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn($scenario['platinum'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn($scenario['lumber'])->byDefault();
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->andReturn($scenario['totalLand'])->byDefault();

            $this->assertEquals($scenario['expectedMaxAfford'], $this->sut->getMaxAfford($this->dominionMock));
        }
    }
}
