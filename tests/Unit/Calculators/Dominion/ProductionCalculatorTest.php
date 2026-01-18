<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\PrestigeCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ProductionCalculator::class)]
class ProductionCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominion;

    /** @var Mock|HeroCalculator */
    protected $heroCalculator;

    /** @var Mock|ImprovementCalculator */
    protected $improvementCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|PopulationCalculator */
    protected $populationCalculator;

    /** @var Mock|PrestigeCalculator */
    protected $prestigeCalculator;

    /** @var Mock|SpellCalculator */
    protected $spellCalculator;

    /** @var Mock|GuardMembershipService */
    protected $guardMembershipService;

    /** @var Mock|ProductionCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dominion = m::mock(Dominion::class);

        $this->sut = m::mock(ProductionCalculator::class, [
            $this->heroCalculator = m::mock(HeroCalculator::class),
            $this->improvementCalculator = m::mock(ImprovementCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->populationCalculator = m::mock(PopulationCalculator::class),
            $this->prestigeCalculator = m::mock(PrestigeCalculator::class),
            $this->spellCalculator = m::mock(SpellCalculator::class),
            $this->guardMembershipService = m::mock(GuardMembershipService::class),
        ])->makePartial();
    }

    public function testGetPlatinumProduction()
    {
        $this->sut->shouldReceive('getPlatinumProductionRaw')->with($this->dominion)->andReturn(2000.0);
        $this->sut->shouldReceive('getPlatinumProductionMultiplier')->with($this->dominion)->andReturn(1.25);

        $result = $this->sut->getPlatinumProduction($this->dominion);

        $this->assertEquals(2500, $result); // rfloor(2000.0 * 1.25)
    }

    public function testGetPlatinumProductionRaw()
    {
        // Mock employed population (peasant tax)
        $this->populationCalculator->shouldReceive('getPopulationEmployed')
            ->with($this->dominion)->andReturn(1000);

        // Mock building alchemy
        $this->dominion->shouldReceive('getAttribute')->with('building_alchemy')->andReturn(50);

        // Mock spell perk for alchemy bonus
        $this->dominion->shouldReceive('getSpellPerkValue')
            ->with('platinum_production_raw')->andReturn(15); // +15 to base 45

        $result = $this->sut->getPlatinumProductionRaw($this->dominion);

        // Expected calculation:
        // Peasant tax: 1000 * 2.7 = 2700
        // Alchemy: 50 * (45 + 15) = 50 * 60 = 3000
        // Total: 2700 + 3000 = 5700
        $this->assertEquals(5700.0, $result);
    }

    public function testGetPlatinumProductionMultiplier()
    {
        // Mock dominion race methods
        $mockRace = m::mock();
        $mockRace->shouldReceive('getPerkMultiplier')->with('platinum_production')->andReturn(0.10);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($mockRace);

        // Mock dominion tech/wonder methods
        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('platinum_production')->andReturn(0.125);
        $this->dominion->shouldReceive('getTechPerkValue')->with('guard_tax')->andReturn(1); // +1% guard tax
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('platinum_production')->andReturn(0.05);
        $this->dominion->shouldReceive('getWonderPerkValue')->with('guard_tax')->andReturn(0);

        // Mock spell calculator
        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'platinum_production')->andReturn(10); // 10%

        // Mock improvement calculator
        $this->improvementCalculator->shouldReceive('getImprovementMultiplierBonus')
            ->with($this->dominion, 'science')->andReturn(0.08);

        // Mock hero calculator
        $this->heroCalculator->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominion, 'platinum_production')->andReturn(0.03);

        // Mock guard membership - is a guard member
        $this->guardMembershipService->shouldReceive('isGuardMember')
            ->with($this->dominion)->andReturn(true);

        $result = $this->sut->getPlatinumProductionMultiplier($this->dominion);

        // Expected calculation: 1 + 0.10 + 0.125 + 0.05 + (10/100) + 0.08 + 0.03 - ((2+1)/100)
        // = 1 + 0.10 + 0.125 + 0.05 + 0.10 + 0.08 + 0.03 - 0.03 = 1.455
        $this->assertEqualsWithDelta(1.455, $result, 0.0001);
    }

    public function testGetPlatinumProductionMultiplierNotGuard()
    {
        // Test case where dominion is not a guard member
        $mockRace = m::mock();
        $mockRace->shouldReceive('getPerkMultiplier')->with('platinum_production')->andReturn(0.0);
        $this->dominion->shouldReceive('getAttribute')->with('race')->andReturn($mockRace);

        $this->dominion->shouldReceive('getTechPerkMultiplier')->with('platinum_production')->andReturn(0.0);
        $this->dominion->shouldReceive('getTechPerkValue')->with('guard_tax')->andReturn(0);
        $this->dominion->shouldReceive('getWonderPerkMultiplier')->with('platinum_production')->andReturn(0.0);
        $this->dominion->shouldReceive('getWonderPerkValue')->with('guard_tax')->andReturn(0);

        $this->spellCalculator->shouldReceive('resolveSpellPerk')
            ->with($this->dominion, 'platinum_production')->andReturn(0);

        $this->improvementCalculator->shouldReceive('getImprovementMultiplierBonus')
            ->with($this->dominion, 'science')->andReturn(0.0);

        $this->heroCalculator->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominion, 'platinum_production')->andReturn(0.0);

        // Not a guard member - no guard tax penalty
        $this->guardMembershipService->shouldReceive('isGuardMember')
            ->with($this->dominion)->andReturn(false);

        $result = $this->sut->getPlatinumProductionMultiplier($this->dominion);

        $this->assertEquals(1.0, $result); // Base multiplier with no bonuses or penalties
    }
}
