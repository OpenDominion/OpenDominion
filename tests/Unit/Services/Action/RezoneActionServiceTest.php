<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Mockery as m;
use OpenDominion\Interfaces\Calculators\Dominion\LandCalculatorInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Actions\RezoneActionService;
use PHPUnit_Framework_TestCase;

/**
 * Class RezoneActionServiceTest
 * @package OpenDominion\Tests\Unit\Services\Action
 */
class RezoneActionServiceTest extends PHPUnit_Framework_TestCase
{
    /** @var  \OpenDominion\Services\Actions\RezoneActionService */
    protected $service;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        $landCalculator = m::mock(LandCalculatorInterface::class);
        $landCalculator->shouldReceive('init');
        $landCalculator->shouldReceive('getRezoningPlatinumCost')->andReturn(25);
        $landCalculator->shouldReceive('getTotalBarrenLandByLandType', 'cavern')->andReturn(5);
        $this->service = new RezoneActionService($landCalculator);
    }

    /**
     * {@inheritdoc}
     */
    public function tearDown()
    {
        m::close();
    }

    /**
     * Baseline.
     */
    public function testDoingNothing()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, [], []);
    }

    /**
     * Test converting one cavern to a hill.
     */
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

    /**
     * Test that nothing happens when the target land is the same as the source.
     */
    public function testConvertingToSameTypeIsFree()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, ['cavern' => 1], ['cavern' => 1]);
    }

    /**
     * Test converting multiple land types, including from and to the same type.
     */
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
     * Test that rezoning a locked dominion is prohibited.
     *
     * @expectedException \OpenDominion\Exceptions\DominionLockedException
     */
    public function testRezoningLockedDominion()
    {
        $dominion = $this->getMockDominion();
        $dominion->shouldReceive('isLocked')->once()->andReturn(true);
        $this->service->rezone($dominion, [], []);
    }

    /**
     * Test that the amount of land to add cannot be different from the land to remove.
     *
     * @expectedException \OpenDominion\Exceptions\BadInputException
     */
    public function testMismatchedRezoning()
    {
        $dominion = $this->getMockDominion();
        $this->service->rezone($dominion, ['cavern' => 1], ['hill' => 2]);
    }

    /**
     * Test that only barren land can be converted.
     *
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
     * Test that you cannot perform a conversion you can't afford.
     *
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

    /**
     * Get a mock dominion.
     *
     * @return m\MockInterface
     */
    protected function getMockDominion()
    {
        $dominion = m::mock(Dominion::class);
        $dominion->shouldReceive('isLocked')->andReturn(false)->byDefault();
        return $dominion;
    }
}
