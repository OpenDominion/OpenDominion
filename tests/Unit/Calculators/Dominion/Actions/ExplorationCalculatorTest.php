<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion\Actions;

use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ExplorationCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $dominionMock;

    /** @var Mock|Race */
    protected $raceMock;

    /** @var Mock|HeroCalculator */
    protected $heroCalculator;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|GuardMembershipService */
    protected $guardMembershipService;

    /** @var Mock|ExplorationCalculator */
    protected $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);
        $this->raceMock = m::mock(Race::class);

        $this->sut = m::mock(ExplorationCalculator::class, [
            $this->heroCalculator = m::mock(HeroCalculator::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->guardMembershipService = m::mock(GuardMembershipService::class),
        ])->makePartial();
    }

    /**
     * @dataProvider getPlatinumCostProvider
     */
    public function testGetPlatinumCost(
        int $totalLand,
        float $multiplier,
        int $expectedPlatinumCost
    ) {
        $this->landCalculator
            ->shouldReceive('getTotalLand')
            ->with($this->dominionMock)
            ->andReturn($totalLand);

        $this->sut
            ->shouldReceive('getPlatinumCostMultiplier')
            ->with($this->dominionMock)
            ->andReturn($multiplier);

        $this->assertEquals($expectedPlatinumCost, $this->sut->getPlatinumCost($this->dominionMock));
    }

    public function getPlatinumCostProvider()
    {
        return [
            // [totalLand, multiplier, expectedPlatinumCost]
            [250, 1.0, 604],
            [500, 1.0, 2036],
            [1000, 1.0, 5050],
            [2000, 1.0, 11646],
            [3000, 1.0, 19721],
            // Test with cost reduction multiplier
            [1000, 0.9, 4545],
            [1000, 0.8, 4040],
            // Test with cost increase multiplier (Elite Guard)
            [1000, 1.25, 6313],
            [2000, 1.25, 14558],
        ];
    }

    public function testGetPlatinumCostMultiplier_BaseCase()
    {
        $this->setupBasicDominion();

        $this->raceMock
            ->shouldReceive('getPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getTechPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->heroCalculator
            ->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominionMock, 'explore_cost')
            ->andReturn(0.0);

        $this->guardMembershipService
            ->shouldReceive('isEliteGuardMember')
            ->with($this->dominionMock)
            ->andReturn(false);

        $this->dominionMock
            ->shouldReceive('getSpellPerkValue')
            ->with('explore_cost_wizard_mastery')
            ->andReturn(0);

        $this->assertEquals(1.0, $this->sut->getPlatinumCostMultiplier($this->dominionMock));
    }

    public function testGetPlatinumCostMultiplier_WithRacialBonus()
    {
        $this->setupBasicDominion();

        $this->raceMock
            ->shouldReceive('getPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(-0.1); // 10% cost reduction

        $this->dominionMock
            ->shouldReceive('getTechPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->heroCalculator
            ->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominionMock, 'explore_cost')
            ->andReturn(0.0);

        $this->guardMembershipService
            ->shouldReceive('isEliteGuardMember')
            ->with($this->dominionMock)
            ->andReturn(false);

        $this->dominionMock
            ->shouldReceive('getSpellPerkValue')
            ->with('explore_cost_wizard_mastery')
            ->andReturn(0);

        $this->assertEquals(0.9, $this->sut->getPlatinumCostMultiplier($this->dominionMock));
    }

    public function testGetPlatinumCostMultiplier_WithEliteGuard()
    {
        $this->raceMock
            ->shouldReceive('getAttribute')
            ->with('key')
            ->andReturn('human');

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('race')
            ->andReturn($this->raceMock);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(1);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('wizard_mastery')
            ->andReturn(0);

        $this->raceMock
            ->shouldReceive('getPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getTechPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->heroCalculator
            ->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominionMock, 'explore_cost')
            ->andReturn(0.0);

        $this->guardMembershipService
            ->shouldReceive('isEliteGuardMember')
            ->with($this->dominionMock)
            ->andReturn(true);

        $this->dominionMock
            ->shouldReceive('getSpellPerkValue')
            ->with('explore_cost_wizard_mastery')
            ->andReturn(0);

        $this->assertEquals(1.25, $this->sut->getPlatinumCostMultiplier($this->dominionMock));
    }

    /**
     * @dataProvider getPlatinumCostMultiplierExcludedRacesProvider
     */
    public function testGetPlatinumCostMultiplier_ExcludedRaces(
        string $raceKey,
        float $techBonus,
        float $expectedMultiplier
    ) {
        $this->setupBasicDominion($raceKey);

        $this->raceMock
            ->shouldReceive('getPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getTechPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn($techBonus);

        $this->dominionMock
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->heroCalculator
            ->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominionMock, 'explore_cost')
            ->andReturn(0.0);

        $this->guardMembershipService
            ->shouldReceive('isEliteGuardMember')
            ->with($this->dominionMock)
            ->andReturn(false);

        $this->dominionMock
            ->shouldReceive('getSpellPerkValue')
            ->with('explore_cost_wizard_mastery')
            ->andReturn(0);

        $this->assertEquals($expectedMultiplier, $this->sut->getPlatinumCostMultiplier($this->dominionMock));
    }

    public function getPlatinumCostMultiplierExcludedRacesProvider()
    {
        return [
            // Excluded races get half tech bonus
            ['firewalker', -0.1, 0.95],
            ['goblin', -0.1, 0.95],
            ['lycanthrope', -0.1, 0.95],
            ['vampire', -0.1, 0.95],
            // Non-excluded race gets full tech bonus
            ['human', -0.1, 0.9],
        ];
    }

    public function testGetPlatinumCostMultiplier_WithWizardMastery()
    {
        $this->raceMock
            ->shouldReceive('getAttribute')
            ->with('key')
            ->andReturn('human');

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('race')
            ->andReturn($this->raceMock);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(null);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('wizard_mastery')
            ->andReturn(500);

        $this->raceMock
            ->shouldReceive('getPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getTechPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->dominionMock
            ->shouldReceive('getWonderPerkMultiplier')
            ->with('explore_platinum_cost')
            ->andReturn(0.0);

        $this->heroCalculator
            ->shouldReceive('getHeroPerkMultiplier')
            ->with($this->dominionMock, 'explore_cost')
            ->andReturn(0.0);

        $this->guardMembershipService
            ->shouldReceive('isEliteGuardMember')
            ->with($this->dominionMock)
            ->andReturn(false);

        $this->dominionMock
            ->shouldReceive('getSpellPerkValue')
            ->with('explore_cost_wizard_mastery')
            ->andReturn(2000); // 500 mastery / 2000 / 100 = 0.0025 reduction

        // 1.0 - 0.0025 = 0.9975
        $this->assertEquals(0.9975, $this->sut->getPlatinumCostMultiplier($this->dominionMock));
    }

    /**
     * @dataProvider getDrafteeCostProvider
     */
    public function testGetDrafteeCost(
        int $totalLand,
        int $techPerkValue,
        int $expectedDrafteeCost
    ) {
        $this->landCalculator
            ->shouldReceive('getTotalLand')
            ->with($this->dominionMock)
            ->andReturn($totalLand);

        $this->dominionMock
            ->shouldReceive('getTechPerkValue')
            ->with('explore_draftee_cost')
            ->andReturn($techPerkValue);

        $this->assertEquals($expectedDrafteeCost, $this->sut->getDrafteeCost($this->dominionMock));
    }

    public function getDrafteeCostProvider()
    {
        return [
            // [totalLand, techPerkValue, expectedDrafteeCost]
            [250, 0, 4], // rfloor(250/150) + 3 = 1 + 3 = 4
            [500, 0, 6], // rfloor(500/150) + 3 = 3 + 3 = 6
            [1000, 0, 9], // rfloor(1000/150) + 3 = 6 + 3 = 9
            [2000, 0, 16], // rfloor(2000/150) + 3 = 13 + 3 = 16
            [3000, 0, 23], // rfloor(3000/150) + 3 = 20 + 3 = 23
            // With tech perk value
            [1000, 1, 10], // 9 + 1 = 10
            [1000, -1, 8], // 9 - 1 = 8
        ];
    }

    /**
     * @dataProvider getMaxAffordProvider
     */
    public function testGetMaxAfford(
        int $platinum,
        int $draftees,
        int $platinumCost,
        int $drafteeCost,
        int $expectedMaxAfford
    ) {
        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('resource_platinum')
            ->andReturn($platinum);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('military_draftees')
            ->andReturn($draftees);

        $this->sut
            ->shouldReceive('getPlatinumCost')
            ->with($this->dominionMock)
            ->andReturn($platinumCost);

        $this->sut
            ->shouldReceive('getDrafteeCost')
            ->with($this->dominionMock)
            ->andReturn($drafteeCost);

        $this->assertEquals($expectedMaxAfford, $this->sut->getMaxAfford($this->dominionMock));
    }

    public function getMaxAffordProvider()
    {
        return [
            // [platinum, draftees, platinumCost, drafteeCost, expectedMaxAfford]
            // Limited by platinum
            [10000, 100, 1000, 5, 10],
            [5000, 100, 1000, 5, 5],
            // Limited by draftees
            [100000, 20, 1000, 5, 4],
            [100000, 50, 1000, 5, 10],
            // Balanced resources
            [5000, 25, 1000, 5, 5],
            // Edge case: can't afford any
            [500, 2, 1000, 5, 0],
        ];
    }

    /**
     * @dataProvider getMoraleDropProvider
     */
    public function testGetMoraleDrop(
        int $totalLand,
        int $amount,
        int $expectedMoraleDrop
    ) {
        $this->landCalculator
            ->shouldReceive('getTotalLand')
            ->with($this->dominionMock)
            ->andReturn($totalLand);

        $this->assertEquals($expectedMoraleDrop, $this->sut->getMoraleDrop($this->dominionMock, $amount));
    }

    public function getMoraleDropProvider()
    {
        return [
            // [totalLand, amount, expectedMoraleDrop]
            [250, 10, 4], // max(1, rfloor((10 + 2) / 3)) = max(1, 4) = 4
            [250, 30, 10], // max(1, rfloor((30 + 2) / 3)) = max(1, 10) = 10
            [500, 60, 20], // max(1, rfloor((60 + 2) / 3)) = max(1, 20) = 20
            [1000, 100, 34], // max(1, rfloor((100 + 2) / 3)) = max(1, 34) = 34
            // Edge case: very small amounts
            [250, 1, 1], // max(1, rfloor((1 + 2) / 3)) = max(1, 1) = 1
            [250, 2, 1], // max(1, rfloor((2 + 2) / 3)) = max(1, 1) = 1
        ];
    }

    /**
     * Helper method to set up basic dominion mock
     */
    protected function setupBasicDominion(string $raceKey = 'human'): void
    {
        $this->raceMock
            ->shouldReceive('getAttribute')
            ->with('key')
            ->andReturn($raceKey);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('race')
            ->andReturn($this->raceMock);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('user_id')
            ->andReturn(null);

        $this->dominionMock
            ->shouldReceive('getAttribute')
            ->with('wizard_mastery')
            ->andReturn(0);
    }
}
