<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\RoundWonder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WonderCalculator::class)]
class WonderCalculatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetCycloneDamageAppliesTechHeroAndNeutralWonderModifiers(): void
    {
        $dominion = m::mock(Dominion::class);
        $hero = m::mock(Hero::class);
        $wonder = m::mock(RoundWonder::class);

        $dominion->shouldReceive('getTechPerkMultiplier')->with('wonder_damage')->andReturn(0.2);
        $dominion->shouldReceive('getAttribute')->with('hero')->andReturn($hero);
        $hero->shouldReceive('getPerkMultiplier')->with('cyclone_damage')->andReturn(0.1);
        $wonder->shouldReceive('getAttribute')->with('realm_id')->andReturn(null);
        $wonder->shouldReceive('getAttribute')->with('power')->andReturn(1000000);

        $calculator = $this->makeCalculator($dominion, 0.5, 1000);

        $this->assertSame(1950, $calculator->getCycloneDamage($dominion, $wonder));
    }

    public function testGetCycloneDamageDoesNotDoubleDamageForOwnedWonder(): void
    {
        $dominion = m::mock(Dominion::class);
        $wonder = m::mock(RoundWonder::class);

        $dominion->shouldReceive('getTechPerkMultiplier')->with('wonder_damage')->andReturn(0.2);
        $dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);
        $wonder->shouldReceive('getAttribute')->with('realm_id')->andReturn(123);
        $wonder->shouldReceive('getAttribute')->with('power')->andReturn(1000000);

        $calculator = $this->makeCalculator($dominion, 0.5, 1000);

        $this->assertSame(900, $calculator->getCycloneDamage($dominion, $wonder));
    }

    public function testGetCycloneDamageRespectsWonderPowerCap(): void
    {
        $dominion = m::mock(Dominion::class);
        $wonder = m::mock(RoundWonder::class);

        $dominion->shouldReceive('getTechPerkMultiplier')->with('wonder_damage')->andReturn(0.0);
        $dominion->shouldReceive('getAttribute')->with('hero')->andReturn(null);
        $wonder->shouldReceive('getAttribute')->with('realm_id')->andReturn(null);
        $wonder->shouldReceive('getAttribute')->with('power')->andReturn(100000);

        $calculator = $this->makeCalculator($dominion, 1.0, 1000);

        $this->assertSame(750, $calculator->getCycloneDamage($dominion, $wonder));
    }

    private function makeCalculator(Dominion $dominion, float $wizardRatio, int $land): WonderCalculator
    {
        $landCalculator = m::mock(LandCalculator::class);
        $militaryCalculator = m::mock(MilitaryCalculator::class);

        $landCalculator->shouldReceive('getTotalLand')->with($dominion)->andReturn($land);
        $militaryCalculator->shouldReceive('getWizardRatioRaw')->with($dominion)->andReturn($wizardRatio);

        return new WonderCalculator($landCalculator, $militaryCalculator);
    }
}
