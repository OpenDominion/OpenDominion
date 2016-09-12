<?php

namespace OpenDominion\Tests\Unit\Calculators\Networth;

use Mockery as m;
use OpenDominion\Calculators\Networth\UnitNetworthCalculator;
use OpenDominion\Models\Unit;
use OpenDominion\Tests\BaseTestCase;

class UnitNetworthCalculatorTest extends BaseTestCase
{
    public function testCalculateMethod()
    {
        $unit1 = m::mock(Unit::class);
        $unit2 = m::mock(Unit::class);
        $unit3 = m::mock(Unit::class);
        $unit4 = m::mock(Unit::class);

        $unitNetworthCalculator = new UnitNetworthCalculator();

        $unit1->shouldReceive('getAttribute')->with('slot')->andReturn(1);
        $unit2->shouldReceive('getAttribute')->with('slot')->andReturn(2);
        $unit3->shouldReceive('getAttribute')->with('slot')->andReturn(3);
        $unit3->shouldReceive('getAttribute')->with('power_offense')->times(3)->andReturn(2);
        $unit3->shouldReceive('getAttribute')->with('power_defense')->times(3)->andReturn(6);
        $unit4->shouldReceive('getAttribute')->with('slot')->andReturn(4);
        $unit4->shouldReceive('getAttribute')->with('power_offense')->times(3)->andReturn(6);
        $unit4->shouldReceive('getAttribute')->with('power_defense')->times(3)->andReturn(3);

        $this->assertEquals(5, $unitNetworthCalculator->calculate($unit1));
        $this->assertEquals(5, $unitNetworthCalculator->calculate($unit2));
        $this->assertEquals(11.7, $unitNetworthCalculator->calculate($unit3));
        $this->assertEquals(12.15, $unitNetworthCalculator->calculate($unit4));
    }
}
