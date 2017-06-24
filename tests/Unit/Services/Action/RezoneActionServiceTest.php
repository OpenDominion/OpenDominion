<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Mockery as m;
use OpenDominion\Interfaces\Calculators\Dominion\LandCalculatorInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Actions\RezoneActionService;
use OpenDominion\Tests\TestCase;

class RezoneActionServiceTest extends TestCase
{
    /** @var  \OpenDominion\Services\Actions\RezoneActionService */
    protected $service;

    public function setUp()
    {
        parent::setUp();
        $landCalculator = m::mock(LandCalculatorInterface::class);
        $landCalculator->shouldReceive('init');
        $landCalculator->shouldReceive('getRezoningPlatinumCost')->andReturn(25)->byDefault();
        $landCalculator->shouldReceive('getTotalBarrenLandByLandType', 'cavern')->andReturn(5)->byDefault();
        $this->service = new RezoneActionService($landCalculator);
    }

    public function testDoingNothing()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, [], []);
    }

    public function testConvertCavernToHill()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('getAttribute')->with('land_cavern')->andReturn(1);
        $dominion->shouldReceive('setAttribute')->with('land_cavern', 0);
        $dominion->shouldReceive('getAttribute')->with('land_hill')->andReturn(0);
        $dominion->shouldReceive('setAttribute')->with('land_hill', 1);
        $dominion->shouldReceive('getAttribute')->with('resource_platinum')->andReturn(100);
        $dominion->shouldReceive('setAttribute')->with('resource_platinum', 75);
        $dominion->shouldReceive('save');
        $this->service->rezone($dominion, ['cavern' => 1], ['hill' => 1]);
    }

    public function testConvertingToSameTypeIsFree()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, ['cavern' => 1], ['cavern' => 1]);
    }

    public function testMixedConversionWithSameTypeIncluded()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('getAttribute')->with('land_cavern')->andReturn(100);
        $dominion->shouldReceive('setAttribute')->with('land_cavern', 98);
        $dominion->shouldReceive('getAttribute')->with('land_hill')->andReturn(100);
        $dominion->shouldReceive('setAttribute')->with('land_hill', 98);
        $dominion->shouldReceive('getAttribute')->with('land_plain')->andReturn(100);
        $dominion->shouldReceive('setAttribute')->with('land_plain', 104);
        $dominion->shouldReceive('getAttribute')->with('resource_platinum')->andReturn(200);
        $dominion->shouldReceive('setAttribute')->with('resource_platinum', 100);
        $dominion->shouldReceive('save');
        $this->service->rezone($dominion, ['cavern' => 10, 'hill' => 2], ['cavern' => 8, 'plain' => 4]);
    }

    /**
     * @expectedException \OpenDominion\Exceptions\DominionLockedException
     */
    public function testRezoningLockedDominion()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('isLocked')->once()->andReturn(true);
        $this->service->rezone($dominion, [], []);
    }

    /**
     * @expectedException \OpenDominion\Exceptions\BadInputException
     */
    public function testMismatchedRezoning()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, ['cavern' => 1], ['hill' => 2]);
    }

    /**
     * @expectedException \OpenDominion\Exceptions\NotEnoughResourcesException
     */
    public function testRemovingMoreThanBarrenLand()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('getAttribute')->with('land_cavern')->andReturn(10);
        $dominion->shouldReceive('getAttribute')->with('land_hill')->andReturn(10);
        $dominion->shouldReceive('getAttribute')->with('resource_platinum')->andReturn(100000);
        $this->service->rezone($dominion, ['cavern' => 10], ['hill' => 10]);
    }

    /**
     * @expectedException \OpenDominion\Exceptions\NotEnoughResourcesException
     */
    public function testRemovingMoreThanCanBeAfforded()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('getAttribute')->with('land_cavern')->andReturn(1);
        $dominion->shouldReceive('getAttribute')->with('land_hill')->andReturn(0);
        $dominion->shouldReceive('getAttribute')->with('resource_platinum')->andReturn(1);
        $this->service->rezone($dominion, ['cavern' => 1], ['hill' => 1]);
    }

    protected function getMockDominion()
    {
        $dominion = m::mock(Dominion::class);
        $dominion->shouldReceive('isLocked')->andReturn(false)->byDefault();
        return $dominion;
    }
}
