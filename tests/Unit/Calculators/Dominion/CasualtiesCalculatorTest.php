<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\CasualtiesCalculator
 */
class CasualtiesCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|CasualtiesCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(CasualtiesCalculator::class, [
            $this->app->make(LandCalculator::class),
            $this->app->make(PopulationCalculator::class),
            $this->app->make(SpellCalculator::class),
            $this->app->make(UnitHelper::class),
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(CasualtiesCalculator::class, $this->app->make(CasualtiesCalculator::class));
    }

    /**
     * @covers ::getStarvationCasualtiesByUnitType
     * @covers ::getStarvationUnitTypes
     */
    public function testGetStarvationCasualtiesByUnitType()
    {
        $tests = [

            // Enough food: No casualties
            [
                'attributes' => [
                    'resource_food' => 100
                ],
                'expected' => [],
            ],

            // -100 food scenario a
            [
                'attributes' => [
                    'resource_food' => -100,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                    'military_unit1' => 50,
                    'military_unit2' => 150,
                    'military_spies' => 25,
                    'military_wizards' => 25,
                ],
                'expected' => [
                    'peasants' => 27,
                    'military_draftees' => 2,
                    'military_unit1' => 1,
                    'military_unit2' => 3,
                ],
            ],

            // -100 food scenario b
            [
                'attributes' => [
                    'resource_food' => -100,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                ],
                'expected' => [
                    'peasants' => 26,
                    'military_draftees' => 2,
                ],
            ],

        ];

        $this->dominion->shouldReceive('getAttribute')->with('id')->andReturn($this->dominion->id);

        foreach ($tests as $test) {
            // Set attribute default to 0
            $attributes = [
                'resource_food',
                'peasants',
                'military_draftees',
                'military_unit1',
                'military_unit2',
                'military_unit3',
                'military_unit4',
                'military_spies',
                'military_wizards',
                'military_archmages',
            ];

            foreach ($attributes as $attribute) {
                $this->dominion->shouldReceive('getAttribute')->with($attribute)->andReturn(0)->byDefault();
            }

            foreach ($test['attributes'] as $attribute => $value) {
                $this->dominion->shouldReceive('getAttribute')->with($attribute)->andReturn($value)->byDefault();
            }

            $this->dominion->shouldReceive('getAttribute')->with('queues')->andReturn(new \Illuminate\Database\Eloquent\Collection())->byDefault();
            $this->assertEquals($test['expected'], $this->sut->getStarvationCasualtiesByUnitType($this->dominion, $test['attributes']['resource_food']));
        }
    }

//    /**
//     * @covers ::getTotalStarvationCasualties
//     */
//    public function testGetTotalStarvationCasualties()
//    {
//        $this->assertEquals(0, $this->sut->getTotalStarvationCasualties($this->dominion, 100));
//        $this->assertEquals(0, $this->sut->getTotalStarvationCasualties($this->dominion, 0));
//        $this->assertEquals(400, $this->sut->getTotalStarvationCasualties($this->dominion, -100));
//    }
}
