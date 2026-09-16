<?php

namespace OpenDominion\Tests\Unit\Magic\AdjustedPower;

use OpenDominion\Calculators\Dominion\OpsCalculator;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Covers the saturation of spy and wizard ratios used by success rates.
 */
#[CoversClass(OpsCalculator::class)]
class OpsCalculatorAdjustedPowerTest extends AbstractBrowserKitTestCase
{
    /** @var OpsCalculator */
    protected $opsCalculator;

    /** @var OpsCalculator Identical calculator with adjusted power switched off */
    protected $rawOpsCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->opsCalculator = $this->app->make(OpsCalculator::class);
        $this->rawOpsCalculator = $this->app->make(OpsCalculatorWithoutAdjustedPower::class);
    }

    public function testAdjustedPowerSaturatesTowardsTwo(): void
    {
        $expectations = [
            [0, 0],
            [0.1, 0.1818],
            [0.25, 0.4],
            [0.5, 0.6667],
            [1, 1],
            [2, 1.3333],
            [4, 1.6],
            [1000, 1.998],
        ];

        foreach ($expectations as [$ratio, $expected]) {
            $this->assertEqualsWithDelta(
                $expected,
                $this->opsCalculator->getAdjustedPower($ratio),
                0.0001,
                "Adjusted power for ratio {$ratio}"
            );
        }

        $this->assertLessThan(2, $this->opsCalculator->getAdjustedPower(1000000000));
    }

    public function testAdjustedPowerIsUnchangedAtTheCenter(): void
    {
        $this->assertSame(1.0, $this->opsCalculator->getAdjustedPower(1));
    }

    public function testBlackOperationSuccessUsesAdjustedPower(): void
    {
        // 2.0 against 1.0 is a relative ratio of 1.3333 instead of 2.0
        $this->assertEqualsWithDelta(
            $this->rawOpsCalculator->blackOperationSuccessChance(1.3333, 1, 0, 0),
            $this->opsCalculator->blackOperationSuccessChance(2, 1, 0, 0),
            0.0001,
            'Black op success should use adjusted power'
        );
    }

    public function testInfoOperationSuccessUsesAdjustedPower(): void
    {
        $this->assertEqualsWithDelta(
            $this->rawOpsCalculator->infoOperationSuccessChance(1.3333, 1, 0, 0),
            $this->opsCalculator->infoOperationSuccessChance(2, 1, 0, 0),
            0.0001,
            'Info op success should use adjusted power'
        );
    }

    public function testTheftOperationSuccessUsesAdjustedPower(): void
    {
        $this->assertEqualsWithDelta(
            $this->rawOpsCalculator->theftOperationSuccessChance(1.3333, 1, 0, 0),
            $this->opsCalculator->theftOperationSuccessChance(2, 1, 0, 0),
            0.0001,
            'Theft op success should use adjusted power'
        );
    }

    /**
     * Matched ratios are unaffected by saturation, whatever they are.
     */
    public function testEqualRatiosAreUnchanged(): void
    {
        foreach ([0.1, 0.5, 1, 2, 5] as $ratio) {
            $this->assertEqualsWithDelta(
                $this->rawOpsCalculator->blackOperationSuccessChance($ratio, $ratio, 0, 0),
                $this->opsCalculator->blackOperationSuccessChance($ratio, $ratio, 0, 0),
                0.0001,
                "Success at matched ratio {$ratio} should not change"
            );
        }
    }

    /**
     * The defender's own investment caps how far ahead an attacker can get:
     * the relative ratio cannot exceed 2 / adjustedPower(target).
     */
    public function testTargetRatioCapsAttackerSuccess(): void
    {
        $ceiling = $this->rawOpsCalculator->blackOperationSuccessChance(2, 1, 0, 0);

        foreach ([4, 10, 100, 10000] as $selfRatio) {
            $successChance = $this->opsCalculator->blackOperationSuccessChance($selfRatio, 1, 0, 0);

            $this->assertLessThanOrEqual(
                $ceiling,
                $successChance,
                "Ratio {$selfRatio} against a 1.0 defender should not exceed the 2:1 ceiling"
            );
        }

        $this->assertEqualsWithDelta(
            $ceiling,
            $this->opsCalculator->blackOperationSuccessChance(100000000, 1, 0, 0),
            0.0001,
            'An unlimited ratio should approach the ceiling set by the defender'
        );
    }

    /**
     * Saturation compresses both sides, so outmatched attackers do better than
     * they do today and heavily outmatching attackers do worse.
     */
    public function testSaturationCompressesBothExtremes(): void
    {
        $this->assertGreaterThan(
            $this->rawOpsCalculator->blackOperationSuccessChance(0.5, 2, 0, 0),
            $this->opsCalculator->blackOperationSuccessChance(0.5, 2, 0, 0),
            'An outmatched attacker should do better with adjusted power'
        );

        $this->assertLessThan(
            $this->rawOpsCalculator->blackOperationSuccessChance(4, 0.5, 0, 0),
            $this->opsCalculator->blackOperationSuccessChance(4, 0.5, 0, 0),
            'An overwhelming attacker should do worse with adjusted power'
        );
    }

    public function testStrengthModifierStillApplies(): void
    {
        $withoutStrength = $this->opsCalculator->blackOperationSuccessChance(1, 1, 0, 0);
        $withStrength = $this->opsCalculator->blackOperationSuccessChance(1, 1, 100, 0);

        $this->assertEqualsWithDelta($withoutStrength + 0.1, $withStrength, 0.0001);
    }

    public function testTargetsWithoutAnyPowerAreAlwaysHit(): void
    {
        $this->assertSame(1.0, $this->opsCalculator->blackOperationSuccessChance(0.1, 0, 0, 0));
        $this->assertSame(1.0, $this->opsCalculator->infoOperationSuccessChance(0.1, 0, 0, 0));
        $this->assertSame(1.0, $this->opsCalculator->theftOperationSuccessChance(0.1, 0, 0, 0));
    }

    public function testSuccessChanceRemainsClamped(): void
    {
        $this->assertEqualsWithDelta(
            0.97,
            $this->opsCalculator->blackOperationSuccessChance(2, 0.001, 100, 0),
            0.0001
        );

        $this->assertEqualsWithDelta(
            0.01,
            $this->opsCalculator->blackOperationSuccessChance(0.001, 2, 0, 100),
            0.0001
        );
    }

    /**
     * With the flag off, every success rate matches the pre-saturation formula.
     */
    public function testFlagDisabledRestoresRawRatios(): void
    {
        $expectations = [
            [1, 1, 0.594117],
            [2, 1, 0.797209],
            [4, 1, 0.906059],
            [0.5, 1, 0.302339],
        ];

        foreach ($expectations as [$selfRatio, $targetRatio, $expected]) {
            $this->assertEqualsWithDelta(
                $expected,
                $this->rawOpsCalculator->blackOperationSuccessChance($selfRatio, $targetRatio, 0, 0),
                0.0001,
                "Raw success for {$selfRatio} against {$targetRatio}"
            );
        }
    }
}

/**
 * Test double used to compare against the behavior the flag switches off.
 */
class OpsCalculatorWithoutAdjustedPower extends OpsCalculator
{
    protected const USE_ADJUSTED_POWER = false;
}
