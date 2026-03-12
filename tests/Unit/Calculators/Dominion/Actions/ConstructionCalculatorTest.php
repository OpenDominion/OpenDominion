<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ConstructionCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var Round */
    protected $roundMock;

    /** @var Mock|BuildingCalculator */
    protected $buildingCalculator;

    /** @var Mock|HeroCalculator */
    protected $heroCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|ConstructionCalculator */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);
        $this->roundMock = m::mock(Round::class);

        $this->sut = m::mock(ConstructionCalculator::class, [
            $this->buildingCalculator = m::mock(BuildingCalculator::class),
            $this->heroCalculator = m::mock(HeroCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->spellCalculator = m::mock(SpellCalculator::class),
        ])->makePartial();
    }

    public function testGetPlatinumCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedPlatinumCost' => 850],
            ['totalBuildings' => 2000, 'totalLand' => 2000, 'expectedPlatinumCost' => 3038],
            ['totalBuildings' => 4000, 'totalLand' => 4000, 'expectedPlatinumCost' => 5538],
            ['totalBuildings' => 6000, 'totalLand' => 6000, 'expectedPlatinumCost' => 8038],
            ['totalBuildings' => 8000, 'totalLand' => 8000, 'expectedPlatinumCost' => 10538],
        ];

        $this->sut->shouldReceive('getPlatinumCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_conquered')->andReturn(0)->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_lost')->andReturn(0)->byDefault();

            $this->assertEquals($scenario['expectedPlatinumCost'], $this->sut->getPlatinumCost($this->dominionMock));
        }
    }

    public function testGetLumberCost()
    {
        $scenarios = [
            ['totalBuildings' => 90, 'totalLand' => 250, 'expectedLumberCost' => 88],
            ['totalBuildings' => 2000, 'totalLand' => 2000, 'expectedLumberCost' => 586],
            ['totalBuildings' => 4000, 'totalLand' => 4000, 'expectedLumberCost' => 1156],
            ['totalBuildings' => 6000, 'totalLand' => 6000, 'expectedLumberCost' => 1726],
            ['totalBuildings' => 8000, 'totalLand' => 8000, 'expectedLumberCost' => 2296],
        ];

        $this->sut->shouldReceive('getLumberCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        foreach ($scenarios as $scenario) {
            $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalBuildings'])->byDefault();
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_conquered')->andReturn(0)->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_lost')->andReturn(0)->byDefault();

            $this->assertEquals($scenario['expectedLumberCost'], $this->sut->getLumberCost($this->dominionMock));
        }
    }

    public function testDiscountedLandMultiplier()
    {
        $scenarios = [
            ['daysInRound' => 1, 'expectedModifier' => 0.5],
            ['daysInRound' => 10, 'expectedModifier' => 0.5],
            ['daysInRound' => 20, 'expectedModifier' => 0.5],
            ['daysInRound' => 30, 'expectedModifier' => 0.4625],
            ['daysInRound' => 40, 'expectedModifier' => 0.3875],
            ['daysInRound' => 50, 'expectedModifier' => 0.35],
        ];

        foreach ($scenarios as $scenario) {
            $this->roundMock->shouldReceive('daysInRound')->andReturn($scenario['daysInRound'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('round')->andReturn($this->roundMock)->byDefault();

            $this->assertEquals($scenario['expectedModifier'], $this->sut->getDiscountedLandMultiplier($this->dominionMock));
        }
    }

    #[DataProvider('getGetMaxAffordProvider')]
    public function testGetMaxAfford(
        /** @noinspection PhpDocSignatureInspection */
        int $totalBuildings,
        int $totalLand,
        int $totalBarrenLand,
        int $platinum,
        int $lumber,
        int $discountedLand,
        int $expectedMaxAfford,
        int $stat_total_land_conquered,
        int $stat_total_land_lost
    ) {
        $this->roundMock->shouldReceive('daysInRound')->andReturn(3)->byDefault();

        $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn($platinum)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('resource_lumber')->andReturn($lumber)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('discounted_land')->andReturn($discountedLand)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_conquered')->andReturn($stat_total_land_conquered)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_lost')->andReturn($stat_total_land_lost)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('round')->andReturn($this->roundMock)->byDefault();

        $this->buildingCalculator->shouldReceive('getTotalBuildings')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalBuildings)->byDefault();

        $this->landCalculator->shouldReceive('getTotalBarrenLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalBarrenLand)->byDefault();
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalLand)->byDefault();

        $this->sut->shouldReceive('getPlatinumCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);
        $this->sut->shouldReceive('getLumberCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        $this->assertEquals($expectedMaxAfford, $this->sut->getMaxAfford($this->dominionMock));

    }

    public static function getGetMaxAffordProvider()
    {
        return [
            // new dominion: totalBuildings, totalLand, totalBarrenLand, platinum, lumber, discountedLand, expectedMaxAfford, stat_total_land_conquered, stat_total_land_lost
            [90, 250, 160, 100000, 15000, 0, 117, 0, 0],
            [2000, 5000, 3000, 1000000, 150000, 0, 104, 0, 0],
            [4000, 8000, 4000, 10000000, 1500000, 0, 653, 0, 0],
            // discounted_land scenario
            [450, 500, 50, 10000, 15000, 10, 13, 50, 0],
        ];
    }
}
