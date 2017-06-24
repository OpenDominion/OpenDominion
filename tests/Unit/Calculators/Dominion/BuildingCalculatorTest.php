<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class BuildingCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculatorTestMock; // todo: rename systems under test to $sut in every test class

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->buildingCalculatorTestMock = m::mock(BuildingCalculator::class)->makePartial(); // todo: check makePartial needed after refactor
        $this->buildingCalculatorTestMock->initDependencies(); // todo
        $this->buildingCalculatorTestMock->init($this->dominionMock); // todo
    }

    public function testGetTotalBuildings()
    {
        $buildingTypes = [
            'home',
            'alchemy',
            'farm',
            'smithy',
            'masonry',
            'ore_mine',
            'gryphon_nest',
            'tower',
            'wizard_guild',
            'temple',
            'diamond_mine',
            'school',
            'lumberyard',
            'forest_haven',
            'factory',
            'guard_tower',
            'shrine',
            'barracks',
            'dock',
        ];

        $expected = 0;

        foreach ($buildingTypes as $buildingType) {
            $this->dominionMock->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(1);
            $expected++; // todo: use this style for every dominion land/building/etc iteration test
        }

        $this->assertEquals($expected, $this->buildingCalculatorTestMock->getTotalBuildings());
    }
}
