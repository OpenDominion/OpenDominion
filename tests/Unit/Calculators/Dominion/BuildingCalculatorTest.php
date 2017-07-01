<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class BuildingCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $sut;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->sut = m::mock(BuildingCalculator::class, [
            m::mock(BuildingHelper::class)->makePartial(),
        ])->makePartial();
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
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalBuildings($this->dominionMock));
    }
}
