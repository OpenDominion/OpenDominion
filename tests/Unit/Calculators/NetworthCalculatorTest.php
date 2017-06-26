<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Mockery as m;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class NetworthCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var NetworthCalculator */
    protected $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->buildingCalculator = m::mock(BuildingCalculator::class);
        $this->landCalculator = m::mock(LandCalculator::class);

        $this->sut = m::mock(NetworthCalculator::class, [$this->buildingCalculator, $this->landCalculator])->makePartial();
    }

    public function testGetRealmNetworth()
    {
        /** @var Realm $realmMock */
        $realmMock = m::mock(Realm::class);

        $dominions = [];

        for ($i = 0; $i < 5; $i++) {
            $dominion = m::mock(Dominion::class);

            $this->sut->shouldReceive('getDominionNetworth')->with($dominion)->andReturn(100);

            $dominions[] = $dominion;
        }

        $realmMock->shouldReceive('getAttribute')->with('dominions')->andReturn($dominions);

        $this->assertEquals(500, $this->sut->getRealmNetworth($realmMock));
    }

    public function testGetDominionNetworth()
    {
        /** @var Dominion $dominionMock */
        $dominionMock = m::mock(Dominion::class);

        $this->buildingCalculator ->shouldReceive('setDominion')->with($dominionMock);
        $this->landCalculator->shouldReceive('setDominion')->with($dominionMock);

        $units = [];
        for ($slot = 1; $slot <= 4; $slot++) {
            $dominionMock->shouldReceive('getAttribute')->with("military_unit{$slot}")->andReturn(100);

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

        $dominionMock->shouldReceive('getAttribute')->with('race')->andReturn($race);
        $dominionMock->shouldReceive('getAttribute')->with('military_spies')->andReturn(25);
        $dominionMock->shouldReceive('getAttribute')->with('military_wizards')->andReturn(25);
        $dominionMock->shouldReceive('getAttribute')->with('military_archmages')->andReturn(0);

        $this->landCalculator->shouldReceive('getTotalLand')->with($dominionMock)->andReturn(250);
        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($dominionMock)->andReturn(90);

        $this->assertEquals(8950, $this->sut->getDominionNetworth($dominionMock));
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

        $this->assertEquals(5, $this->sut->getUnitNetworth($unit1));
        $this->assertEquals(5, $this->sut->getUnitNetworth($unit2));
        $this->assertEquals(11.7, $this->sut->getUnitNetworth($unit3));
        $this->assertEquals(12.15, $this->sut->getUnitNetworth($unit4));
    }
}
