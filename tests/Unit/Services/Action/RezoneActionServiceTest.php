<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RezoneActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var Dominion */
    protected $dominion;

    /** @var RezoneActionService */
    protected $rezoneActionService;

    public function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->dominion = $this->createDominion($user, $round);
        $this->rezoneActionService = $this->app->make(RezoneActionService::class);
    }

    public function tearDown()
    {
        // todo: add this to other test classes
        m::close();
    }

    /**
     * Baseline.
     */
    public function testDoingNothing()
    {
        $this->assertEquals(100000, $this->dominion->resource_platinum);

        $this->rezoneActionService->rezone($this->dominion, [], []);

        $this->assertEquals(100000, $this->dominion->resource_platinum);
    }

    /**
     * Test converting one cavern to a hill.
     */
    public function testConvertCavernToHill()
    {
        $this->assertEquals(20, $this->dominion->land_cavern);
        $this->assertEquals(20, $this->dominion->land_hill);

        $this->rezoneActionService->rezone($this->dominion, ['cavern' => 5], ['hill' => 5]);

        $this->assertEquals(15, $this->dominion->land_cavern);
        $this->assertEquals(25, $this->dominion->land_hill);
    }

    /**
     * Test that nothing happens when the target land is the same as the source.
     */
    public function testConvertingToSameTypeIsFree()
    {
        $this->assertEquals(100000, $this->dominion->resource_platinum);
        $this->assertEquals(20, $this->dominion->land_cavern);

        $this->rezoneActionService->rezone($this->dominion, ['cavern' => 5], ['cavern' => 5]);

        $this->assertEquals(100000, $this->dominion->resource_platinum);
        $this->assertEquals(20, $this->dominion->land_cavern);
    }

    /**
     * Test converting multiple land types, including from and to the same type.
     */
    public function testMixedConversionWithSameTypeIncluded()
    {
        $this->assertEquals(110, $this->dominion->land_plain);
        $this->assertEquals(20, $this->dominion->land_cavern);
        $this->assertEquals(20, $this->dominion->land_hill);

        $this->rezoneActionService->rezone($this->dominion,
            ['cavern' => 10, 'hill' => 2],
            ['cavern' => 8, 'plain' => 4]
        );

        $this->assertEquals(114, $this->dominion->land_plain);
        $this->assertEquals(18, $this->dominion->land_cavern);
        $this->assertEquals(18, $this->dominion->land_hill);
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
        $this->rezoneActionService->rezone($dominion, [], []);
    }

    /**
     * Test that the amount of land to add cannot be different from the land to remove.
     *
     * @expectedException \OpenDominion\Exceptions\BadInputException
     */
    public function testMismatchedRezoning()
    {
        $dominion = $this->getMockDominion();
        $this->rezoneActionService->rezone($dominion, ['cavern' => 1], ['hill' => 2]);
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
        $this->rezoneActionService->rezone($dominion, ['cavern' => 10], ['hill' => 10]);
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
        $this->rezoneActionService->rezone($dominion, ['cavern' => 1], ['hill' => 1]);
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
