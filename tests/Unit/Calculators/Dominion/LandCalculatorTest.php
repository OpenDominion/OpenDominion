<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(LandCalculator::class)]
class LandCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|QueueService */
    protected $queueService;

    /** @var Mock|LandCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(LandCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->app->make(BuildingHelper::class),
            $this->app->make(LandHelper::class),
            $this->queueService = m::mock(QueueService::class),
        ])->makePartial();
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(LandCalculator::class, $this->app->make(LandCalculator::class));
    }

    public function testGetTotalLand()
    {
        $expected = 0;

        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(1);
            $expected++;
        }

        $this->assertEquals($expected, $this->sut->getTotalLand($this->dominion));
    }

    public function testGetTotalBarrenLand()
    {
        foreach ($this->getLandTypes() as $landType) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(10);
        }

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominion)->andReturn(1);
        $this->queueService->shouldReceive('getConstructionQueueTotal')->with($this->dominion)->andReturn(2);

        $this->assertEquals(67, $this->sut->getTotalBarrenLand($this->dominion));
    }

    public function testGetTotalBarrenLandByLandType()
    {
        $this->markTestIncomplete();
    }

    public function testGetBarrenLandByLandType()
    {
        /** @var Mock|Race $raceMock */
        $raceMock = m::mock(Race::class);
        $raceMock->shouldReceive('getAttribute')->with('home_land_type')->andReturn('plain');

        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($raceMock);

        $buildingTypesByLandType = [
            'plain' => [
                'home',
                'alchemy',
                'farm',
                'smithy',
                'masonry',
            ],
            'mountain' => [
                'ore_mine',
                'gryphon_nest',
            ],
            'swamp' => [
                'tower',
                'wizard_guild',
                'temple',
            ],
            'cavern' => [
                'diamond_mine',
                'school',
            ],
            'forest' => [
                'lumberyard',
                //'forest_haven',
            ],
            'hill' => [
                'factory',
                'guard_tower',
                'shrine',
                'barracks',
            ],
            'water' => [
                'dock',
            ],
        ];

        $expected = array_combine(
            array_keys($buildingTypesByLandType),
            array_fill(0, \count($buildingTypesByLandType), 0)
        );

        foreach ($buildingTypesByLandType as $landType => $buildingTypes) {
            $this->dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn(100);
            $expected[$landType] += 100;

            foreach ($buildingTypes as $buildingType) {
                $this->dominion->shouldReceive('getAttribute')->with('building_' . $buildingType)->andReturn(2);
                $this->queueService->shouldReceive('getConstructionQueueTotalByResource')->with($this->dominion, "building_{$buildingType}")->andReturn(1);
                $expected[$landType] -= 3;
            }
        }

        $this->assertEquals($expected, $this->sut->getBarrenLandByLandType($this->dominion));
    }

    /**
     * The Town Crier reports the sum of the per-land-type losses while the
     * "your dominion was invaded" notification reports the requested acres,
     * so the two disagree unless the per-type losses add up exactly.
     */
    public function testGetLandLostByLandTypeLosesExactlyTheRequestedAcres(): void
    {
        $scenarios = [
            // Distributions that lost one acre too few while the total was floored
            ['land' => ['plain' => 481, 'mountain' => 38, 'swamp' => 70, 'cavern' => 153, 'forest' => 982, 'hill' => 4, 'water' => 0], 'acresLost' => 222],
            ['land' => ['plain' => 219, 'mountain' => 40, 'swamp' => 14, 'cavern' => 686, 'forest' => 178, 'hill' => 84, 'water' => 0], 'acresLost' => 244],
            ['land' => ['plain' => 379, 'mountain' => 1229, 'swamp' => 573, 'cavern' => 451, 'forest' => 191, 'hill' => 44, 'water' => 0], 'acresLost' => 457],
            // Distributions that divide evenly
            ['land' => ['plain' => 300, 'mountain' => 200, 'swamp' => 0, 'cavern' => 0, 'forest' => 0, 'hill' => 0, 'water' => 0], 'acresLost' => 100],
            ['land' => ['plain' => 250, 'mountain' => 0, 'swamp' => 0, 'cavern' => 0, 'forest' => 0, 'hill' => 0, 'water' => 0], 'acresLost' => 10],
        ];

        foreach ($scenarios as $scenario) {
            $totalLand = array_sum($scenario['land']);
            $landLostByLandType = $this->getLandLostByLandType($scenario['land'], $scenario['acresLost']);

            $this->assertEquals(
                $scenario['acresLost'],
                array_sum(array_column($landLostByLandType, 'landLost')),
                sprintf('Expected %s of %s acres to be lost', $scenario['acresLost'], $totalLand)
            );
        }
    }

    public function testGetLandLostByLandTypeDistributesLossProportionally(): void
    {
        $land = ['plain' => 481, 'mountain' => 38, 'swamp' => 70, 'cavern' => 153, 'forest' => 982, 'hill' => 4, 'water' => 0];

        $landLostByLandType = $this->getLandLostByLandType($land, 222);

        $expected = [
            'forest' => 127,
            'plain' => 62,
            'cavern' => 20,
            'swamp' => 9,
            // Truncated to the acres left to lose, which is why hill is untouched
            'mountain' => 4,
        ];

        $this->assertEquals($expected, array_map(function ($landLost) {
            return $landLost['landLost'];
        }, $landLostByLandType));
    }

    public function testGetLandLostByLandTypeNeverLosesMoreLandThanALandTypeHas(): void
    {
        $land = ['plain' => 1, 'mountain' => 1, 'swamp' => 1, 'cavern' => 1, 'forest' => 1244, 'hill' => 1, 'water' => 1];

        $landLostByLandType = $this->getLandLostByLandType($land, 623);

        $this->assertEquals(623, array_sum(array_column($landLostByLandType, 'landLost')));

        foreach ($landLostByLandType as $landType => $landLost) {
            $this->assertLessThanOrEqual($land[$landType], $landLost['landLost'], "Lost more {$landType} than available");
        }
    }

    /**
     * Runs getLandLostByLandType against a fully barren Dominion, using the
     * same ratio the invasion and wonder services pass in.
     *
     * @param array<string, int> $landByLandType
     * @return array<string, array{landLost: int, barrenLandLost: int, buildingsToDestroy: int}>
     */
    private function getLandLostByLandType(array $landByLandType, int $acresLost): array
    {
        /** @var Mock|LandCalculator $sut */
        $sut = m::mock(LandCalculator::class, [
            m::mock(BuildingCalculator::class),
            $this->app->make(BuildingHelper::class),
            $this->app->make(LandHelper::class),
            m::mock(QueueService::class),
        ])->makePartial();

        /** @var Mock|Dominion $dominion */
        $dominion = m::mock(Dominion::class);

        foreach ($landByLandType as $landType => $acres) {
            $dominion->shouldReceive('getAttribute')->with('land_' . $landType)->andReturn($acres);
        }

        $sut->shouldReceive('getBarrenLandByLandType')->with($dominion)->andReturn($landByLandType);

        return $sut->getLandLostByLandType($dominion, $acresLost / array_sum($landByLandType));
    }

    /**
     * Returns all the land types.
     *
     * todo: Maybe refactor to $this->landHelper->getLandTypes()?
     *
     * @return array
     */
    private function getLandTypes(): array
    {
        return [
            'plain',
            'mountain',
            'swamp',
            'cavern',
            'forest',
            'hill',
            'water',
        ];
    }
}
