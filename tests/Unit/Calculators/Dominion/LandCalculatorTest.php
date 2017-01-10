<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\BaseTestCase;

class LandCalculatorTest extends BaseTestCase
{
    /** @var Dominion */
    protected $dominion;

    /** @var LandCalculator */
    protected $landCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->landCalculator = $this->app->make(LandCalculator::class)
            ->setDominion($this->dominion);
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
            $this->dominion->shouldReceive('getAttribute')->with("land_{$landTypes[$i]}")->andReturn(1 << $i);
            $expected += (1 << $i);
        }

        $this->assertEquals($expected, $this->landCalculator->getTotalLand());
    }

    public function testGetTotalBarrenLand()
    {
        $this->markTestIncomplete();
    }

    public function testGetBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }
}
