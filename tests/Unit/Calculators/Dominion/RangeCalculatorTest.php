<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Illuminate\Support\Collection;
use Mockery as m;
use Mockery\Mock;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\RangeCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

/**
 * @coversDefaultClass \OpenDominion\Calculators\Dominion\RangeCalculator
 */
class RangeCalculatorTest extends AbstractBrowserKitTestCase
{
    /** @var Mock|Dominion */
    protected $selfDominion;

    /** @var Mock|Dominion */
    protected $targetDominion;

    /** @var Mock|GuardMembershipService */
    protected $guardMembershipService;

    /** @var Mock|LandCalculator */
    protected $landCalculator;

    /** @var Mock|MilitaryCalculator */
    protected $militaryCalculator;

    /** @var Mock|ProtectionService */
    protected $protectionService;

    /** @var Mock|RangeCalculator */
    protected $sut;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->selfDominion = m::mock(Dominion::class);
        $this->targetDominion = m::mock(Dominion::class);

        $this->sut = m::mock(RangeCalculator::class, [
            $this->guardMembershipService = m::mock(GuardMembershipService::class),
            $this->landCalculator = m::mock(LandCalculator::class),
            $this->militaryCalculator = m::mock(MilitaryCalculator::class),
            $this->protectionService = m::mock(ProtectionService::class),
        ])->makePartial();
    }

    public function testIsInRangeBasicRange()
    {
        // Test basic range calculation with no guard status
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(600);

        // Different realms
        $this->selfDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);
        $this->targetDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(2);

        // Mock range modifiers (base minimum range for both)
        $this->sut->shouldReceive('getRangeModifier')->with($this->selfDominion)->andReturn(0.4);
        $this->sut->shouldReceive('getRangeModifier')->with($this->targetDominion)->andReturn(0.4);

        $result = $this->sut->isInRange($this->selfDominion, $this->targetDominion);

        // Expected calculation:
        // Self land: 1000, Target land: 600, Range modifier: 0.4
        // Target range check: 600 >= (1000 * 0.4) = 400 ✓ AND 600 <= (1000 / 0.4) = 2500 ✓
        // Self range check: 1000 >= (600 * 0.4) = 240 ✓ AND 1000 <= (600 / 0.4) = 1500 ✓
        $this->assertTrue($result);
    }

    public function testIsInRangeOutOfRange()
    {
        // Test dominion that is out of range (too small)
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(300); // Too small

        $this->selfDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);
        $this->targetDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(2);

        $this->sut->shouldReceive('getRangeModifier')->with($this->selfDominion)->andReturn(0.4);
        $this->sut->shouldReceive('getRangeModifier')->with($this->targetDominion)->andReturn(0.4);

        $result = $this->sut->isInRange($this->selfDominion, $this->targetDominion);

        // Target is too small: 300 < (1000 * 0.4) = 400
        $this->assertFalse($result);
    }

    public function testIsInRangeSameRealm()
    {
        // Test dominions in the same realm (should use minimum range)
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(600);

        // Same realm
        $this->selfDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);
        $this->targetDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);

        // Range modifiers are overridden for same realm
        $this->sut->shouldReceive('getRangeModifier')->with($this->selfDominion)->andReturn(0.75); // Guard range
        $this->sut->shouldReceive('getRangeModifier')->with($this->targetDominion)->andReturn(0.75);

        $result = $this->sut->isInRange($this->selfDominion, $this->targetDominion);

        // Same realm uses MINIMUM_RANGE (0.4) regardless of guard status
        // 600 >= (1000 * 0.4) = 400 ✓ AND 600 <= (1000 / 0.4) = 2500 ✓
        $this->assertTrue($result);
    }

    public function testGetRangeModifierEliteGuard()
    {
        $this->guardMembershipService->shouldReceive('isEliteGuardMember')->with($this->selfDominion)->andReturn(true);
        $this->guardMembershipService->shouldReceive('isRoyalGuardMember')->with($this->selfDominion)->andReturn(false);

        $result = $this->sut->getRangeModifier($this->selfDominion);

        $this->assertEquals(0.75, $result); // GuardMembershipService::ELITE_GUARD_RANGE
    }

    public function testGetRangeModifierRoyalGuard()
    {
        $this->guardMembershipService->shouldReceive('isEliteGuardMember')->with($this->selfDominion)->andReturn(false);
        $this->guardMembershipService->shouldReceive('isRoyalGuardMember')->with($this->selfDominion)->andReturn(true);

        $result = $this->sut->getRangeModifier($this->selfDominion);

        $this->assertEquals(0.6, $result); // GuardMembershipService::ROYAL_GUARD_RANGE
    }

    public function testGetRangeModifierNoGuard()
    {
        $this->guardMembershipService->shouldReceive('isEliteGuardMember')->with($this->selfDominion)->andReturn(false);
        $this->guardMembershipService->shouldReceive('isRoyalGuardMember')->with($this->selfDominion)->andReturn(false);

        $result = $this->sut->getRangeModifier($this->selfDominion);

        $this->assertEquals(0.4, $result); // MINIMUM_RANGE
    }

    public function testGetDominionRange()
    {
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(750);

        $result = $this->sut->getDominionRange($this->selfDominion, $this->targetDominion);

        $this->assertEquals(75.0, $result); // (750 / 1000) * 100 = 75%
    }

    public function testGetDominionRangeSpanClassRed()
    {
        $this->sut->shouldReceive('getDominionRange')->with($this->selfDominion, $this->targetDominion)->andReturn(150.0);
        $result = $this->sut->getDominionRangeSpanClass($this->selfDominion, $this->targetDominion);
        $this->assertEquals('text-red', $result); // >= 133.33 (100 / 0.75)
    }

    public function testGetDominionRangeSpanClassGreen()
    {
        $this->sut->shouldReceive('getDominionRange')->with($this->selfDominion, $this->targetDominion)->andReturn(80.0);
        $result = $this->sut->getDominionRangeSpanClass($this->selfDominion, $this->targetDominion);
        $this->assertEquals('text-green', $result); // >= 75
    }

    public function testGetDominionRangeSpanClassMuted()
    {
        $this->sut->shouldReceive('getDominionRange')->with($this->selfDominion, $this->targetDominion)->andReturn(65.0);
        $result = $this->sut->getDominionRangeSpanClass($this->selfDominion, $this->targetDominion);
        $this->assertEquals('text-muted', $result); // >= 60
    }

    public function testGetDominionRangeSpanClassGray()
    {
        $this->sut->shouldReceive('getDominionRange')->with($this->selfDominion, $this->targetDominion)->andReturn(50.0);
        $result = $this->sut->getDominionRangeSpanClass($this->selfDominion, $this->targetDominion);
        $this->assertEquals('text-gray', $result); // < 60
    }

    public function testCheckGuardApplicationsRoyalGuardOutOfRange()
    {
        $this->guardMembershipService->shouldReceive('isRoyalGuardApplicant')->with($this->selfDominion)->andReturn(true);
        $this->guardMembershipService->shouldReceive('isEliteGuardApplicant')->with($this->selfDominion)->andReturn(false);

        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(500); // Out of range

        // Target is out of range: 500 < (1000 * 0.6) = 600
        $this->guardMembershipService->shouldReceive('joinRoyalGuard')->with($this->selfDominion)->once();

        $this->sut->checkGuardApplications($this->selfDominion, $this->targetDominion);

        // Assert that mock expectations were verified (joinRoyalGuard called once due to out-of-range violation)
        $this->addToAssertionCount(1);
    }

    public function testCheckGuardApplicationsEliteGuardInRange()
    {
        $this->guardMembershipService->shouldReceive('isRoyalGuardApplicant')->with($this->selfDominion)->andReturn(false);
        $this->guardMembershipService->shouldReceive('isEliteGuardApplicant')->with($this->selfDominion)->andReturn(true);

        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(800); // In range

        // Target is in range: 800 >= (1000 * 0.75) = 750 AND 800 <= (1000 / 0.75) = 1333
        $this->guardMembershipService->shouldReceive('joinEliteGuard')->never();

        $this->sut->checkGuardApplications($this->selfDominion, $this->targetDominion);

        // Assert that mock expectations were verified (joinEliteGuard never called due to in-range status)
        $this->addToAssertionCount(1);
    }

    public function testCheckGuardApplicationsNoGuard()
    {
        $this->guardMembershipService->shouldReceive('isRoyalGuardApplicant')->with($this->selfDominion)->andReturn(false);
        $this->guardMembershipService->shouldReceive('isEliteGuardApplicant')->with($this->selfDominion)->andReturn(false);

        // Should not call any guard methods
        $this->guardMembershipService->shouldReceive('joinRoyalGuard')->never();
        $this->guardMembershipService->shouldReceive('joinEliteGuard')->never();

        $this->sut->checkGuardApplications($this->selfDominion, $this->targetDominion);

        // Assert that mock expectations were verified (no guard methods called for non-guard member)
        $this->addToAssertionCount(1);
    }

    public function testIsInRangeWithGuardModifiers()
    {
        // Test range calculation with Elite Guard vs Royal Guard
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(650);

        $this->selfDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);
        $this->targetDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(2);

        // Self is Elite Guard (0.75), Target is Royal Guard (0.6)
        $this->sut->shouldReceive('getRangeModifier')->with($this->selfDominion)->andReturn(0.75);
        $this->sut->shouldReceive('getRangeModifier')->with($this->targetDominion)->andReturn(0.6);

        $result = $this->sut->isInRange($this->selfDominion, $this->targetDominion);

        // Expected calculation:
        // Target range check: 650 >= (1000 * 0.75) = 750 ✗ (fails first condition)
        $this->assertFalse($result);
    }

    public function testIsInRangeGuardVersusGuard()
    {
        // Test two guard members with closer range requirements
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->selfDominion)->andReturn(1000);
        $this->landCalculator->shouldReceive('getTotalLand')->with($this->targetDominion)->andReturn(850);

        $this->selfDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(1);
        $this->targetDominion->shouldReceive('getAttribute')->with('realm_id')->andReturn(2);

        // Both are Elite Guard (0.75)
        $this->sut->shouldReceive('getRangeModifier')->with($this->selfDominion)->andReturn(0.75);
        $this->sut->shouldReceive('getRangeModifier')->with($this->targetDominion)->andReturn(0.75);

        $result = $this->sut->isInRange($this->selfDominion, $this->targetDominion);

        // Expected calculation:
        // Target range check: 850 >= (1000 * 0.75) = 750 ✓ AND 850 <= (1000 / 0.75) = 1333 ✓
        // Self range check: 1000 >= (850 * 0.75) = 637.5 ✓ AND 1000 <= (850 / 0.75) = 1133 ✓
        $this->assertTrue($result);
    }
}
