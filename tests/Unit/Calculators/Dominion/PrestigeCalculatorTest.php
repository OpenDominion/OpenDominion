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
}
