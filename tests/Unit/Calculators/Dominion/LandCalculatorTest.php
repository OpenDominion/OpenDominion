<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
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

        $this->assertEquals(69, $this->landCalculator->getTotalBarrenLand());
    }

    public function testGetBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }
}
