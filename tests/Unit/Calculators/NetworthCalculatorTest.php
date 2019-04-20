<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\NetworthCalculator
 */
class NetworthCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|MilitaryCalculator */
    protected $militaryCalculator;

    /** @var Mock|NetworthCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sut = m::mock(NetworthCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->militaryCalculator = m::mock(MilitaryCalculator::class),
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(NetworthCalculator::class, app(NetworthCalculator::class));
    }

    /**
     * @covers ::getRealmNetworth
     */
    public function testGetRealmNetworth()
    {
        /** @var Mock|Realm $realm */
        $realm = m::mock(Realm::class);

        $dominions = [];

        for ($i = 0; $i < 5; $i++) {
            $dominion = m::mock(Dominion::class);

            $this->sut->shouldReceive('getDominionNetworth')->with($dominion)->andReturn(100);

            $dominions[] = $dominion;
        }

        $realm->shouldReceive('getAttribute')->with('dominions')->andReturn($dominions);

        $this->assertEquals(500, $this->sut->getRealmNetworth($realm));
    }

    /**
     * @covers ::getDominionNetworth
     */
    public function testGetDominionNetworth()
    {
        /** @var Mock|Dominion $dominion */
        $dominion = m::mock(Dominion::class);

        $this->buildingCalculator->shouldReceive('setDominion')->with($dominion);
        $this->landCalculator->shouldReceive('setDominion')->with($dominion);

        $units = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $dominion->shouldReceive('getAttribute')->with("military_unit{$slot}")->andReturn(100);

            /** @var Mock|Unit $unit */
            $unit = m::mock(Unit::class);
            $unit->shouldReceive('getAttribute')->with('slot')->andReturn($slot);
            $unit->shouldReceive('getAttribute')->with('power_offense')->andReturn(5);
            $unit->shouldReceive('getAttribute')->with('power_defense')->andReturn(5);

            $units[] = $unit;
        }

        /** @var Mock|Race $race */
        $race = m::mock(Race::class);
        $race->shouldReceive('getAttribute')->with('units')->andReturn($units);

        $dominion->shouldReceive('getAttribute')->with('race')->andReturn($race);
        $dominion->shouldReceive('getAttribute')->with('military_spies')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_wizards')->andReturn(25);
        $dominion->shouldReceive('getAttribute')->with('military_archmages')->andReturn(0);

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($dominion)->andReturn(90);
        $this->landCalculator->shouldReceive('getTotalLand')->with($dominion)->andReturn(250);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($dominion, 1)->andReturn(100);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($dominion, 2)->andReturn(100);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($dominion, 3)->andReturn(100);
        $this->militaryCalculator->shouldReceive('getTotalUnitsForSlot')->with($dominion, 4)->andReturn(100);

        $this->assertEquals(8950, $this->sut->getDominionNetworth($dominion));
    }

    /**
     * @covers ::getUnitNetworth
     */
    public function testGetUnitNetworth()
    {
        // Networth for units in slots 1 and 2 is always 5

        /** @var Mock|Unit $unit1 */
        $unit1 = m::mock(Unit::class);
        $unit1->shouldReceive('getAttribute')->with('slot')->andReturn(1);

        /** @var Mock|Unit $unit2 */
        $unit2 = m::mock(Unit::class);
        $unit2->shouldReceive('getAttribute')->with('slot')->andReturn(2);

        $this->assertEquals(5, $this->sut->getUnitNetworth($unit1));
        $this->assertEquals(5, $this->sut->getUnitNetworth($unit2));

         // Networth for units in slots 2 and 3 is based on base offensive/defensive power

        /** @var Mock|Unit $unit3 */
        $unit3 = m::mock(Unit::class);
        $unit3->shouldReceive('getAttribute')->with('slot')->andReturn(3);
        $unit3->shouldReceive('getAttribute')->with('power_offense')->andReturn(2);
        $unit3->shouldReceive('getAttribute')->with('power_defense')->andReturn(6);

        /** @var Mock|Unit $unit4 */
        $unit4 = m::mock(Unit::class);
        $unit4->shouldReceive('getAttribute')->with('slot')->andReturn(4);
        $unit4->shouldReceive('getAttribute')->with('power_offense')->andReturn(6);
        $unit4->shouldReceive('getAttribute')->with('power_defense')->andReturn(3);

        $this->assertEquals(11.7, $this->sut->getUnitNetworth($unit3));
        $this->assertEquals(12.15, $this->sut->getUnitNetworth($unit4));
    }
}
