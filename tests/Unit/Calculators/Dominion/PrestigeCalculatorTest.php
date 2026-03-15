<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\PrestigeCalculator
 */
class PrestigeCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|Dominion */
    protected $target;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|MilitaryCalculator */
    protected $militaryCalculator;

    /** @var Mock|PrestigeCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);
        $this->target = m::mock(Dominion::class);
        $this->race = m::mock(Race::class);
        $this->realm = m::mock(Realm::class);

        $this->sut = m::mock(PrestigeCalculator::class, [
            $this->governmentService = m::mock(GovernmentService::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->militaryCalculator = m::mock(MilitaryCalculator::class),
        ])->makePartial();
    }

    /**
     * @dataProvider getPrestigeGainProvider
     */
    public function testGetPrestigeGain(
        /** @noinspection PhpDocSignatureInspection */
        int $expected,
        int $attackerLand,
        int $defenderLand,
        bool $warBonus,
        bool $mutualWarBonus
    ) {
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominion)->atLeast($this->once())->andReturn($attackerLand)->byDefault();
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->target)->atLeast($this->once())->andReturn($defenderLand)->byDefault();
        $this->dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);
        $this->dominion->shouldReceive('getAttribute')->with('morale')->andReturn(100);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($this->race);
        $this->race->shouldReceive('getPerkMultiplier')->with('prestige_gains')->andReturn(0);
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('prestige_gains')->andReturn(0);
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('prestige_gains')->andReturn(0);
        $this->dominion->shouldReceive('getAttribute')->with('realm')->andReturn($this->realm);
        $this->target->shouldReceive('getAttribute')->with('realm')->andReturn($this->realm);
        $this->governmentService->shouldReceive('isMutualWarEscalated')->with($this->realm, $this->realm)->andReturn($mutualWarBonus);
        $this->governmentService->shouldReceive('isWarEscalated')->with($this->realm, $this->realm)->andReturn($warBonus);

        $this->assertEquals(
            $expected,
            $this->sut->getPrestigeGain($this->dominion, $this->target),
            sprintf(
                "Attacker Land: %s\nDefender Land: %s\nPrestige Gain: %s",
                $expected,
                $attackerLand,
                $defenderLand
            )
        );
    }

    public function getPrestigeGainProvider()
    {
        return [
            [83, 1000, 2000, false, false],
            [73, 1000, 1000, false, false],
            [56, 1000, 850, false, false],
            [35, 1000, 750, false, false],
            [39, 1000, 750, true, false],
            [42, 1000, 750, true, true],
        ];
    }

    /**
     * @dataProvider getPrestigePenaltyProvider
     */
    public function testGetPrestigePenalty(
        /** @noinspection PhpDocSignatureInspection */
        int $expected,
        int $attackerPrestige,
        int $attackerLand,
        int $defenderLand
    ) {
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->dominion)->atLeast($this->once())->andReturn($attackerLand)->byDefault();
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->target)->atLeast($this->once())->andReturn($defenderLand)->byDefault();
        $this->dominion->shouldReceive('getAttribute')->with('prestige')->atLeast($this->once())->andReturn($attackerPrestige)->byDefault();
        $this->target->shouldReceive('getAttribute')->with('user_id')->andReturn(1);

        $this->assertEquals(
            $expected,
            $this->sut->getPrestigePenalty($this->dominion, $this->target),
            sprintf(
                "Attacker Land: %s\nDefender Land: %s\nPrestige Gain: %s",
                $expected,
                $attackerLand,
                $defenderLand
            )
        );
    }

    public function getPrestigePenaltyProvider()
    {
        return [
            [-45, 250, 1000, 599],
            [-64, 250, 1000, 500],
            [-100, 250, 1000, 400],
            [-75, 1500, 1000, 500],
        ];
    }

    /**
     * @dataProvider getPrestigeGainMultiplierMoraleProvider
     */
    public function testGetPrestigeGainMultiplier_WithMorale(
        int $morale,
        float $expectedMultiplier
    ) {
        $this->dominion
            ->shouldReceive('getAttribute')
            ->with('morale')
            ->andReturn($morale);

        $this->dominion
            ->shouldReceive('getAttribute')
            ->with('race')
            ->andReturn($this->race);

        $this->race
            ->shouldReceive('getPerkMultiplier')
            ->with('prestige_gains')
            ->andReturn(0);

        $this->dominion
            ->shouldReceive('getTechPerkMultiplier')
            ->with('prestige_gains')
            ->andReturn(0);

        $this->dominion
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('prestige_gains')
            ->andReturn(0);

        $this->dominion
            ->shouldReceive('getAttribute')
            ->with('realm')
            ->andReturn($this->realm);

        $this->target
            ->shouldReceive('getAttribute')
            ->with('realm')
            ->andReturn($this->realm);

        $this->governmentService
            ->shouldReceive('isMutualWarEscalated')
            ->with($this->realm, $this->realm)
            ->andReturn(false);

        $this->governmentService
            ->shouldReceive('isWarEscalated')
            ->with($this->realm, $this->realm)
            ->andReturn(false);

        $this->assertEquals(
            $expectedMultiplier,
            $this->sut->getPrestigeGainMultiplier($this->dominion, $this->target)
        );
    }

    public function getPrestigeGainMultiplierMoraleProvider()
    {
        return [
            // [morale, expectedMultiplier]
            [100, 1.0],  // 1 - ((100 - 100) / 100) = 1 - 0 = 1.0
            [95, 0.95],  // 1 - ((100 - 95) / 100) = 1 - 0.05 = 0.95
            [90, 0.90],  // 1 - ((100 - 90) / 100) = 1 - 0.10 = 0.90
            [85, 0.85],  // 1 - ((100 - 85) / 100) = 1 - 0.15 = 0.85
            [80, 0.80],  // 1 - ((100 - 80) / 100) = 1 - 0.20 = 0.80
            [70, 0.70],  // 1 - ((100 - 70) / 100) = 1 - 0.30 = 0.70
            [50, 0.50],  // 1 - ((100 - 50) / 100) = 1 - 0.50 = 0.50
        ];
    }

    /**
     * @dataProvider getPrestigeLossProvider
     */
    public function testGetPrestigeLoss(
        int $targetPrestige,
        int $expectedPrestigeLoss
    ) {
        $this->target
            ->shouldReceive('getAttribute')
            ->with('prestige')
            ->andReturn($targetPrestige);

        $this->assertEquals(
            $expectedPrestigeLoss,
            $this->sut->getPrestigeLoss($this->target)
        );
    }

    public function getPrestigeLossProvider()
    {
        return [
            // [targetPrestige, expectedPrestigeLoss]
            // Base case: 5% loss
            [1000, -50],   // base: 1000 * 0.05 = 50
            [2000, -100],  // base: 2000 * 0.05 = 100
            [500, -25],    // base: 500 * 0.05 = 25
        ];
    }

    /**
     * @dataProvider getPrestigeLossWithCapProvider
     */
    public function testGetPrestigeLoss_WithPrestigeGainCap(
        int $targetPrestige,
        int $prestigeGain,
        int $expectedPrestigeLoss
    ) {
        $this->target
            ->shouldReceive('getAttribute')
            ->with('prestige')
            ->andReturn($targetPrestige);

        $this->assertEquals(
            $expectedPrestigeLoss,
            $this->sut->getPrestigeLoss($this->target, $prestigeGain)
        );
    }

    public function getPrestigeLossWithCapProvider()
    {
        return [
            // [targetPrestige, prestigeGain, expectedPrestigeLoss]
            // prestigeGain caps the base loss
            [1000, 30, -30],   // base: min(50, 30) = 30, total: -30
            [1000, 40, -40],   // base: min(50, 40) = 40, total: -40
            [2000, 80, -80],   // base: min(100, 80) = 80, total: -80

            // prestigeGain doesn't cap because base is lower
            [1000, 100, -50],  // base: min(50, 100) = 50, total: -50
            [1000, 60, -50],   // base: min(50, 60) = 50, total: -50
        ];
    }
}
