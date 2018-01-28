<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\CasualtiesCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\CasualtiesCalculator
 */
class CasualtiesCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|CasualtiesCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->sut = m::mock(CasualtiesCalculator::class, [
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

            // -100 food on starter military: Split 50:50 between peasants and other
            // military. Military is further split evenly
            [
                'attributes' => [
                    'resource_food' => -100,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                    'military_unit2' => 150,
                    'military_spies' => 25,
                    'military_wizards' => 25,
                ],
                'expected' => [
                    'peasants' => 200,
                    'military_unit2' => 75,
                    'military_spies' => 25,
                    'military_wizards' => 25,
                    'military_draftees' => 75,
                ],
            ],

            // Negative remaining casualties
            [
                'attributes' => [
                    'resource_food' => -3,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                    'military_unit2' => 150,
                    'military_spies' => 25,
                    'military_wizards' => 25,
                ],
                'expected' => [
                    'peasants' => 6,
                    'military_draftees' => 1,
                    'military_unit2' => 2,
                    'military_spies' => 2,
                    'military_wizards' => 1,
                ],
            ],

            // Positive remaining casualties, kill more peasants
            [
                'attributes' => [
                    'resource_food' => -100,
                    'peasants' => 1300,
                    'military_draftees' => 100,
                ],
                'expected' => [
                    'peasants' => 300,
                    'military_draftees' => 100,
                ],
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

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
                $dominion->shouldReceive('getAttribute')->with($attribute)->andReturn(0)->byDefault();
            }

            foreach ($test['attributes'] as $attribute => $value) {
                $dominion->shouldReceive('getAttribute')->with($attribute)->andReturn($value)->byDefault();
            }

            $this->assertEquals($test['expected'], $this->sut->getStarvationCasualtiesByUnitType($dominion));
        }
    }

    /**
     * @covers ::getTotalStarvationCasualties
     */
    public function testGetTotalStarvationCasualties()
    {
        /** @var Mock|Dominion $dominion */
        $dominion = m::mock(Dominion::class);

        $dominion->shouldReceive('getAttribute')->with('resource_food')->andReturn(100)->byDefault();
        $this->assertEquals(0, $this->sut->getTotalStarvationCasualties($dominion));

        $dominion->shouldReceive('getAttribute')->with('resource_food')->andReturn(0)->byDefault();
        $this->assertEquals(0, $this->sut->getTotalStarvationCasualties($dominion));

        $dominion->shouldReceive('getAttribute')->with('resource_food')->andReturn(-100)->byDefault();
        $this->assertEquals(400, $this->sut->getTotalStarvationCasualties($dominion));
    }
}
