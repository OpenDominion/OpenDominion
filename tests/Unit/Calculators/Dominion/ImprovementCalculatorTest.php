<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\ImprovementCalculator
 */
class ImprovementCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|ImprovementCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->landCalculator = m::mock(LandCalculator::class);

        $this->sut = m::mock(ImprovementCalculator::class, [
            $this->landCalculator,
        ])->makePartial();
    }

    /**
     * @covers ::__construct
     */
    public function testConstructor()
    {
        $this->assertInstanceOf(ImprovementCalculator::class, app(ImprovementCalculator::class));
    }

    /**
     * @covers ::getImprovementMultiplierBonus
     * @covers ::getImprovementMaximum
     * @covers ::getImprovementCoefficient
     */
    public function testGetImprovementMultiplierBonus()
    {
        $tests = [

            // 250 land, 0 points
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'science', 'improvement_points' => 0],
                'expected' => 0, // 0%
            ],

            // 250 land, 100m points (sure), max
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'science', 'improvement_points' => 100000000],
                'expected' => 0.2, // +20%. Cheater
            ],

            // Science: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'science', 'improvement_points' => 120000],
                'expected' => 0.0223, // +2.23%
            ],

            // Science: 2000 land, 10m points
            [
                'attributes' => ['land' => 1000, 'improvement_type' => 'science', 'improvement_points' => 10000000],
                'expected' => 0.1834, // +18.34
            ],

            // Science: 4000 land, 30m points
            [
                'attributes' => ['land' => 4000, 'improvement_type' => 'science', 'improvement_points' => 30000000],
                'expected' => 0.1693, // +16.93
            ],

            // Science: 4000 land, 30m points, 10% masonries
            [
                'attributes' => [
                    'land' => 4000,
                    'improvement_type' => 'science',
                    'improvement_points' => 30000000,
                    'building_masonry' => (4000 / 10)
                ],
                'expected' => 0.2158, // +21.58
            ],

            // Keep: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'keep', 'improvement_points' => 120000],
                'expected' => 0.0335, // +3.35%
            ],

            // Keep: 4000 land, 30m points
            [
                'attributes' => ['land' => 4000, 'improvement_type' => 'keep', 'improvement_points' => 30000000],
                'expected' => 0.2539, // +25.39
            ],

            // Towers: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'towers', 'improvement_points' => 120000],
                'expected' => 0.0362, // +3.62%
            ],

            // Forges: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'forges', 'improvement_points' => 120000],
                'expected' => 0.0185, // +1.85%
            ],

            // Walls: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'walls', 'improvement_points' => 120000],
                'expected' => 0.0185, // +1.85%
            ],

            // Harbor: 250 land, 120k points (invest 10k starter gems)
            [
                'attributes' => ['land' => 250, 'improvement_type' => 'harbor', 'improvement_points' => 120000],
                'expected' => 0.0362, // +3.62%
            ],

        ];

        foreach ($tests as $test) {
            /** @var Mock|Dominion $dominion */
            $dominion = m::mock(Dominion::class);

            $dominion->shouldReceive('getAttribute')->with('building_masonry')->andReturn($test['attributes']['building_masonry'] ?? 0)->byDefault();

            $this->landCalculator->shouldReceive('getTotalLand')->with($dominion)->andReturn($test['attributes']['land'])->byDefault();
            $dominion->shouldReceive('getAttribute')->with('improvement_' . $test['attributes']['improvement_type'])->andReturn($test['attributes']['improvement_points'])->byDefault();

            $this->assertEquals(
                $test['expected'],
                $this->sut->getImprovementMultiplierBonus($dominion, $test['attributes']['improvement_type']),
                sprintf(
                    "Improvement: %s (%s points)\nLand: %s\n%s",
                    ucfirst($test['attributes']['improvement_type']),
                    number_format($test['attributes']['improvement_points']),
                    number_format($test['attributes']['land']),
                    isset($test['attributes']['building_masonry'])
                        ? ("Masonries: " . number_format($test['attributes']['building_masonry']) . "\n")
                        : ""
                )
            );
        }
    }
}
