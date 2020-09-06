<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery as m;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\RezoneActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RezoneActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var RezoneActionService */
    protected $rezoneActionService;

    public function setUp()
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($user, $this->round);
        $this->landCalculator = $this->app->make(LandCalculator::class);
        $this->rezoneActionService = $this->app->make(RezoneActionService::class);
    }

    public function tearDown()
    {
        // todo: add this to other test classes
        m::close();
    }

    /**
     * Baseline.
     *
     * @expectedException \OpenDominion\Exceptions\GameException
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
     *
     * @expectedException \OpenDominion\Exceptions\GameException
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

        $this->rezoneActionService->rezone(
            $this->dominion,
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
     * @expectedException \RuntimeException
     */
    public function testRezoningLockedDominion()
    {
        $this->assertFalse($this->dominion->isLocked());

        // todo: investigate why $this->round->end_date doesn't work
        $this->dominion->round->end_date = new Carbon('yesterday');

        $this->assertTrue($this->dominion->isLocked());

        $this->rezoneActionService->rezone($this->dominion, [], []);
    }

    /**
     * Test that the amount of land to add cannot be different from the land to remove.
     *
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testMismatchedRezoning()
    {
        $this->rezoneActionService->rezone($this->dominion, ['cavern' => 1], ['hill' => 2]);
    }

    /**
     * Test that only barren land can be converted.
     *
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testRemovingMoreThanBarrenLand()
    {
        $this->dominion->land_cavern = 20;
        $this->dominion->building_diamond_mine = 15;

        $this->assertEquals(5, $this->landCalculator->getTotalBarrenLandByLandType($this->dominion, 'cavern'));

        $this->rezoneActionService->rezone($this->dominion, ['cavern' => 10], ['hill' => 10]);
    }

    /**
     * Test that you cannot perform a conversion you can't afford.
     *
     * @expectedException \OpenDominion\Exceptions\GameException
     */
    public function testRemovingMoreThanCanBeAfforded()
    {
        $this->dominion->resource_platinum = 0;

        $this->rezoneActionService->rezone($this->dominion, ['cavern' => 1], ['hill' => 1]);
    }
}
