<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\NetworthCalculator;
use OpenDominion\Calculators\Networth\UnitNetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Unit;
use OpenDominion\Tests\BaseTestCase;

class NetworthCalculatorTest extends BaseTestCase
{
    public function testGetNetworth()
    {
        $unitNetworthCalculator = m::mock(UnitNetworthCalculator::class);
        $dominion = m::mock(Dominion::class);

        $units = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $unit = m::mock(Unit::class);

            $unitNetworthCalculator->shouldReceive('calculate')->with($unit)->andReturn(5);
            $dominion->shouldReceive('getAttribute')->with("military_unit{$slot}")->andReturn(100);
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($slot);

            $units[] = $unit;
        }

        $race = m::mock(Race::class);
        $race->shouldReceive('getAttribute')->with('units')->andReturn($units);

        $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race);
        $dominion->shouldReceive('getAttribute')->with('military_spies')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_wizards')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_archmages')->andReturn(0);

        // todo: land
        // todo: buildings

        $dominionNetworthCalculator = new NetworthCalculator($dominion, $unitNetworthCalculator);

        $this->assertEquals(2250, $dominionNetworthCalculator->getNetworth());
    }
}
