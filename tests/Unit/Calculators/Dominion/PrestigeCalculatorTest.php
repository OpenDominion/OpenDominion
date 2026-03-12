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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(PrestigeCalculator::class)]
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

    #[DataProvider('getPrestigeGainProvider')]
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

    public static function getPrestigeGainProvider()
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

    #[DataProvider('getPrestigePenaltyProvider')]
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

    public static function getPrestigePenaltyProvider()
    {
        return [
            [-45, 250, 1000, 599],
            [-64, 250, 1000, 500],
            [-100, 250, 1000, 400],
            [-75, 1500, 1000, 500],
        ];
    }

    #[DataProvider('getPrestigeGainMultiplierMoraleProvider')]
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

    public static function getPrestigeGainMultiplierMoraleProvider()
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

    #[DataProvider('getPrestigeLossProvider')]
    public function testGetPrestigeLoss(
        int $targetPrestige,
        int $weeklyInvadedCount,
        int $expectedPrestigeLoss
    ) {
        $this->target
            ->shouldReceive('getAttribute')
            ->with('prestige')
            ->andReturn($targetPrestige);

        $this->militaryCalculator
            ->shouldReceive('getRecentlyInvadedCount')
            ->with($this->target, 168, true)
            ->andReturn($weeklyInvadedCount);

        $this->assertEquals(
            $expectedPrestigeLoss,
            $this->sut->getPrestigeLoss($this->target)
        );
    }

    public static function getPrestigeLossProvider()
    {
        return [
            // [targetPrestige, weeklyInvadedCount, expectedPrestigeLoss]
            // Base case: 5% loss with no recent invasions
            [1000, 0, -50],   // base: 1000 * 0.05 = 50, additional: 0, total: -50
            [2000, 0, -100],  // base: 2000 * 0.05 = 100, additional: 0, total: -100
            [500, 0, -25],    // base: 500 * 0.05 = 25, additional: 0, total: -25

            // With recent invasions: base 5% + 1% per invasion
            [1000, 1, -60],   // base: 50, additional: 1000 * 0.01 = 10, total: -60
            [1000, 2, -70],   // base: 50, additional: 1000 * 0.02 = 20, total: -70
            [1000, 5, -100],  // base: 50, additional: 1000 * 0.05 = 50, total: -100
            [2000, 3, -160],  // base: 100, additional: 2000 * 0.03 = 60, total: -160

            // Cap at 15% (base + invasions capped)
            [1000, 10, -150], // base: 50, additional: 100, total capped at 150
            [1000, 15, -150], // base: 50, additional: 150, total capped at 150
            [1000, 20, -150], // base: 50, additional: 200, total capped at 150
            [5000, 12, -750], // base: 250, additional: 600, total capped at 750
        ];
    }

    #[DataProvider('getPrestigeLossWithCapProvider')]
    public function testGetPrestigeLoss_WithPrestigeGainCap(
        int $targetPrestige,
        int $prestigeGain,
        int $weeklyInvadedCount,
        int $expectedPrestigeLoss
    ) {
        $this->target
            ->shouldReceive('getAttribute')
            ->with('prestige')
            ->andReturn($targetPrestige);

        $this->militaryCalculator
            ->shouldReceive('getRecentlyInvadedCount')
            ->with($this->target, 168, true)
            ->andReturn($weeklyInvadedCount);

        $this->assertEquals(
            $expectedPrestigeLoss,
            $this->sut->getPrestigeLoss($this->target, $prestigeGain)
        );
    }

    public static function getPrestigeLossWithCapProvider()
    {
        return [
            // [targetPrestige, prestigeGain, weeklyInvadedCount, expectedPrestigeLoss]
            // prestigeGain caps the base loss
            [1000, 30, 0, -30],   // base: min(50, 30) = 30, additional: 0, total: -30
            [1000, 40, 0, -40],   // base: min(50, 40) = 40, additional: 0, total: -40
            [2000, 80, 0, -80],   // base: min(100, 80) = 80, additional: 0, total: -80

            // prestigeGain doesn't cap because base is lower
            [1000, 100, 0, -50],  // base: min(50, 100) = 50, additional: 0, total: -50
            [1000, 60, 0, -50],   // base: min(50, 60) = 50, additional: 0, total: -50

            // With invasions - base is capped but additional is added
            [1000, 30, 1, -40],   // base: min(50, 30) = 30, additional: 10, total: -40
            [1000, 30, 2, -50],   // base: min(50, 30) = 30, additional: 20, total: -50
            [1000, 30, 5, -80],   // base: min(50, 30) = 30, additional: 50, total: -80
            [2000, 50, 3, -110],  // base: min(100, 50) = 50, additional: 60, total: -110

            // With many invasions - still respects 15% total cap
            [1000, 30, 15, -150], // base: min(50, 30) = 30, additional: 150, capped at 150
            [1000, 10, 20, -150], // base: min(50, 10) = 10, additional: 200, capped at 150
        ];
    }
}
