<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Mockery as m;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Unit;
use OpenDominion\Tests\BaseTestCase;

class NetworthCalculatorTest extends BaseTestCase
{
    /** @var NetworthCalculator */
    protected $networthCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->networthCalculator = $this->app->make(NetworthCalculator::class);
    }

    public function setGetRealmNetworth()
    {
        $this->markTestIncomplete();
    }

    public function testGetDominionNetworth()
    {
        /** @var Dominion $dominion */
        $dominion = m::mock(Dominion::class);

        $units = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $dominion->shouldReceive('getAttribute')->with("military_unit{$slot}")->andReturn(100);

            /** @var Unit $unit */
            $unit = m::mock(Unit::class);
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($slot);
            $unit->shouldReceive('getAttribute')->with('power_offense')->andReturn(5);
            $unit->shouldReceive('getAttribute')->with('power_defense')->andReturn(5);

            $units[] = $unit;
        }

        /** @var Race $race */
        $race = m::mock(Race::class);
        $race->shouldReceive('getAttribute')->with('units')->andReturn($units);

        $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race);
        $dominion->shouldReceive('getAttribute')->with('military_spies')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_wizards')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_archmages')->andReturn(0);

        // todo: land
        // todo: buildings

        $this->assertEquals(3500, $this->networthCalculator->getDominionNetworth($dominion));
    }

    public function testGetUnitNetworth()
    {
        /** @var Unit $unit1 */
        $unit1 = m::mock(Unit::class);
        $unit1->shouldReceive('getAttribute')->with('slot')->andReturn(1);

        /** @var Unit $unit2 */
        $unit2 = m::mock(Unit::class);
        $unit2->shouldReceive('getAttribute')->with('slot')->andReturn(2);

        /** @var Unit $unit3 */
        $unit3 = m::mock(Unit::class);
        $unit3->shouldReceive('getAttribute')->with('slot')->andReturn(3);
        $unit3->shouldReceive('getAttribute')->with('power_offense')->andReturn(2);
        $unit3->shouldReceive('getAttribute')->with('power_defense')->andReturn(6);

        /** @var Unit $unit4 */
        $unit4 = m::mock(Unit::class);
        $unit4->shouldReceive('getAttribute')->with('slot')->andReturn(4);
        $unit4->shouldReceive('getAttribute')->with('power_offense')->andReturn(6);
        $unit4->shouldReceive('getAttribute')->with('power_defense')->andReturn(3);

        $this->assertEquals(5, $this->networthCalculator->getUnitNetworth($unit1));
        $this->assertEquals(5, $this->networthCalculator->getUnitNetworth($unit2));
        $this->assertEquals(11.7, $this->networthCalculator->getUnitNetworth($unit3));
        $this->assertEquals(12.15, $this->networthCalculator->getUnitNetworth($unit4));
    }
}
