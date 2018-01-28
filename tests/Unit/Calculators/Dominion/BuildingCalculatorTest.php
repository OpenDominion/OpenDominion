<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\BuildingCalculator
 */
class BuildingCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|BuildingCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(BuildingCalculator::class, [
            $this->app->make(BuildingHelper::class),
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(BuildingCalculator::class, $this->app->make(BuildingCalculator::class));
    }

    /**
     * @covers ::getTotalBuildings
     */
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
            $this->dominion->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalBuildings($this->dominion));
    }
}
