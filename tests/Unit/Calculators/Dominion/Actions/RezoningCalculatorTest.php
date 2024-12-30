<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RezoningCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var Round */
    protected $roundMock;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|RezoningCalculator */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);
        $this->roundMock = m::mock(Round::class);

        $this->sut = m::mock(RezoningCalculator::class, [
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->spellCalculator = m::mock(SpellCalculator::class),
        ])->makePartial();
    }

    public function testGetPlatinumCost()
    {
        $scenarios = [
            ['totalLand' => 250, 'landConquered' => 0, 'expectedPlatinumCost' => 250],
            ['totalLand' => 750, 'landConquered' => 0, 'expectedPlatinumCost' => 550],
            ['totalLand' => 2000, 'landConquered' => 0, 'expectedPlatinumCost' => 1300],
            ['totalLand' => 2000, 'landConquered' => 1000, 'expectedPlatinumCost' => 900],
            ['totalLand' => 5000, 'landConquered' => 0, 'expectedPlatinumCost' => 3100],
            ['totalLand' => 5000, 'landConquered' => 4000, 'expectedPlatinumCost' => 1500],
        ];

        $this->sut->shouldReceive('getCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        foreach ($scenarios as $scenario) {
            $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($scenario['totalLand'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_conquered')->andReturn($scenario['landConquered'])->byDefault();
            $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_lost')->andReturn(0)->byDefault();

            $this->assertEquals($scenario['expectedPlatinumCost'], $this->sut->getPlatinumCost($this->dominionMock));
        }
    }

    /**
     * @dataProvider getGetMaxAffordProvider
     */
    public function testGetMaxAfford(
        /** @noinspection PhpDocSignatureInspection */
        int $totalLand,
        int $platinum,
        int $expectedMaxAfford,
        int $stat_total_land_conquered,
        int $stat_total_land_lost
    ) {
        $this->roundMock->shouldReceive('daysInRound')->andReturn(3)->byDefault();

        $this->dominionMock->shouldReceive('getAttribute')->with('resource_platinum')->andReturn($platinum)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_conquered')->andReturn($stat_total_land_conquered)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('stat_total_land_lost')->andReturn($stat_total_land_lost)->byDefault();
        $this->dominionMock->shouldReceive('getAttribute')->with('round')->andReturn($this->roundMock)->byDefault();

        $this->landCalculator->shouldReceive('getTotalBarrenLand')->with($this->dominionMock)->atLeast($this->once())->andReturn(150)->byDefault();
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominionMock)->atLeast($this->once())->andReturn($totalLand)->byDefault();

        $this->sut->shouldReceive('getCostMultiplier')->with($this->dominionMock)->atLeast($this->once())->andReturn(1);

        $this->assertEquals($expectedMaxAfford, $this->sut->getMaxAfford($this->dominionMock));

    }

    public function getGetMaxAffordProvider()
    {
        return [
            [ // new dominion - barren limited
                'totalLand' => 250,
                'platinum' => 100000,
                'expectedMaxAfford' => 150,
                'stat_total_conquered_land' => 0,
                'stat_total_land_lost' => 0,
            ],
            [
                'totalLand' => 250,
                'platinum' => 27500,
                'expectedMaxAfford' => 110,
                'stat_total_conquered_land' => 0,
                'stat_total_land_lost' => 0,
            ],
            [
                'totalLand' => 5000,
                'platinum' => 310000,
                'expectedMaxAfford' => 100,
                'stat_total_conquered_land' => 0,
                'stat_total_land_lost' => 0,
            ],
            [
                'totalLand' => 5000,
                'platinum' => 150000,
                'expectedMaxAfford' => 100,
                'stat_total_conquered_land' => 4000,
                'stat_total_land_lost' => 0,
            ],
        ];
    }
}
