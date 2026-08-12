<?php

namespace OpenDominion\Tests\Benchmark;

use OpenDominion\Models\Dominion;
use OpenDominion\Tests\Benchmark\Support\TickProfile;
use PHPUnit\Framework\Attributes\Group;

/**
 * Phase 1: the budgets from phase 0, enforced.
 *
 * These tests FAIL when the tick gets more expensive. Every number they assert
 * against lives in TickBenchmarkBudgets with the measurement that produced it.
 *
 * Run with:
 *   php artisan test --group=benchmark
 *
 * Assertions are on query counts only. Wall time is reported by
 * TickBaselineReportTest but never asserted - it is machine-dependent, and a
 * benchmark that fails because CI was busy is a benchmark people learn to skip.
 */
#[Group('benchmark')]
class TickServiceBenchmarkTest extends AbstractTickBenchmarkTestCase
{
    /**
     * Finding 2.1: precalculateTick re-queries relations the tick already
     * eager-loaded, at roughly 20 queries per dominion.
     */
    public function testPrecalculateTickStaysWithinQueryBudget(): void
    {
        $fixture = $this->seeder->seed(1);
        $tickService = $this->tickService();
        $dominion = Dominion::findOrFail($fixture->dominionIds[0]);

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'precalculateTick budget',
            static function () use ($tickService, $dominion): void {
                $tickService->precalculateTick($dominion);
            },
            1
        );

        $this->assertLessThanOrEqual(
            TickBenchmarkBudgets::PRECALCULATE_MAX_QUERIES,
            $profile->queryCount(),
            $this->explain(
                $profile,
                sprintf(
                    'precalculateTick() issued %d queries, budget is %d.',
                    $profile->queryCount(),
                    TickBenchmarkBudgets::PRECALCULATE_MAX_QUERIES
                )
            )
        );
    }

    /**
     * The single-dominion branch, used by the manual advance-tick path
     * (MiscController.php:404) and by NPD generation.
     */
    public function testSingleDominionTickStaysWithinQueryBudget(): void
    {
        $fixture = $this->seeder->seed(1);
        $tickService = $this->tickService();
        $dominion = Dominion::findOrFail($fixture->dominionIds[0]);

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'single-dominion performTick budget',
            static function () use ($tickService, $fixture, $dominion): void {
                $tickService->performTick($fixture->round, $dominion);
            },
            1
        );

        $this->assertLessThanOrEqual(
            TickBenchmarkBudgets::SINGLE_DOMINION_TICK_MAX_QUERIES,
            $profile->queryCount(),
            $this->explain(
                $profile,
                sprintf(
                    'performTick($round, $dominion) issued %d queries, budget is %d.',
                    $profile->queryCount(),
                    TickBenchmarkBudgets::SINGLE_DOMINION_TICK_MAX_QUERIES
                )
            )
        );
    }

    /**
     * The headline budget: what one more dominion in a round costs.
     */
    public function testPerDominionQueryCountStaysWithinBudget(): void
    {
        $fixture = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $tickService = $this->tickService();

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'per-dominion budget',
            static function () use ($tickService, $fixture): void {
                $tickService->performTick($fixture->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $this->assertLessThanOrEqual(
            TickBenchmarkBudgets::PER_DOMINION_MAX_QUERIES,
            $profile->queriesPerDominion(),
            $this->explain(
                $profile,
                sprintf(
                    'performTick() cost %.2f queries per dominion (%d total for %d dominions), budget is %.2f.',
                    $profile->queriesPerDominion(),
                    $profile->queryCount(),
                    TickBenchmarkBudgets::LARGE_N,
                    TickBenchmarkBudgets::PER_DOMINION_MAX_QUERIES
                )
            )
        );
    }

    /**
     * Guards against superlinearity. A per-dominion budget alone would not catch
     * a cost that only appears at scale - this compares the marginal cost
     * between two round sizes.
     */
    public function testPerformTickScalesLinearlyWithDominionCount(): void
    {
        $small = $this->seeder->seed(TickBenchmarkBudgets::SMALL_N);
        $large = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $tickService = $this->tickService();

        $this->warmUp($small, $large);

        $smallProfile = $this->profiler->profile(
            'scaling, small',
            static function () use ($tickService, $small): void {
                $tickService->performTick($small->round);
            },
            TickBenchmarkBudgets::SMALL_N
        );

        $largeProfile = $this->profiler->profile(
            'scaling, large',
            static function () use ($tickService, $large): void {
                $tickService->performTick($large->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $marginal = ($largeProfile->queryCount() - $smallProfile->queryCount())
            / (TickBenchmarkBudgets::LARGE_N - TickBenchmarkBudgets::SMALL_N);

        $this->assertLessThanOrEqual(
            TickBenchmarkBudgets::MAX_MARGINAL_QUERIES,
            $marginal,
            $this->explain(
                $largeProfile,
                sprintf(
                    "Each additional dominion cost %.2f queries, budget is %.2f.\n"
                    . "N=%d: %d queries. N=%d: %d queries.\n"
                    . 'A marginal cost above the per-dominion average means something is superlinear.',
                    $marginal,
                    TickBenchmarkBudgets::MAX_MARGINAL_QUERIES,
                    TickBenchmarkBudgets::SMALL_N,
                    $smallProfile->queryCount(),
                    TickBenchmarkBudgets::LARGE_N,
                    $largeProfile->queryCount()
                )
            )
        );
    }

    /**
     * Protects the part of the tick that is already well built: phase A applies
     * every precomputed delta for the whole round in three statements, and must
     * stay at three no matter how many dominions there are.
     */
    public function testPhaseAIsConstantRegardlessOfDominionCount(): void
    {
        $small = $this->seeder->seed(TickBenchmarkBudgets::SMALL_N);
        $large = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $tickService = $this->tickService();

        $this->warmUp($small, $large);

        $smallProfile = $this->profiler->profile(
            'phase A, small',
            static function () use ($tickService, $small): void {
                $tickService->performTick($small->round);
            },
            TickBenchmarkBudgets::SMALL_N
        );

        $largeProfile = $this->profiler->profile(
            'phase A, large',
            static function () use ($tickService, $large): void {
                $tickService->performTick($large->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $smallCount = $smallProfile->countMatchingPattern(TickBenchmarkBudgets::PHASE_A_PATTERN);
        $largeCount = $largeProfile->countMatchingPattern(TickBenchmarkBudgets::PHASE_A_PATTERN);

        $this->assertSame(
            TickBenchmarkBudgets::PHASE_A_STATEMENTS,
            $smallCount,
            sprintf(
                "Phase A issued %d bulk statements at N=%d, expected %d. Saw:\n%s",
                $smallCount,
                TickBenchmarkBudgets::SMALL_N,
                TickBenchmarkBudgets::PHASE_A_STATEMENTS,
                $this->formatStatements($smallProfile->statementsMatching(TickBenchmarkBudgets::PHASE_A_PATTERN))
            )
        );

        $this->assertSame(
            $smallCount,
            $largeCount,
            sprintf(
                'Phase A issued %d bulk statements at N=%d but %d at N=%d - it is no longer constant in the number of dominions.',
                $smallCount,
                TickBenchmarkBudgets::SMALL_N,
                $largeCount,
                TickBenchmarkBudgets::LARGE_N
            )
        );
    }

    /**
     * The N+1 detector. Any statement executed once per dominion (or worse, once
     * per row) rises to the top of duplicateGroups().
     *
     * Currently the ceiling is set by the spell_perk_types pivot select at 6 per
     * dominion - finding 2.3's unmemoized perk rebuilds. Lower the budget as
     * those are fixed.
     */
    public function testNoStatementRepeatsMoreThanBudgetPerDominion(): void
    {
        $fixture = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $tickService = $this->tickService();

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'repeat budget',
            static function () use ($tickService, $fixture): void {
                $tickService->performTick($fixture->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $ceiling = TickBenchmarkBudgets::MAX_REPEATS_PER_DOMINION * TickBenchmarkBudgets::LARGE_N;
        $worst = $profile->mostRepeatedStatement();

        $this->assertNotNull($worst, 'Expected the tick to issue at least one repeated statement.');

        [$sql, $count] = $worst;

        $this->assertLessThanOrEqual(
            $ceiling,
            $count,
            sprintf(
                "A single statement ran %d times for %d dominions (%.2f per dominion), budget is %d per dominion.\n"
                . "Statement:\n  %s\n\nTop repeats:\n%s",
                $count,
                TickBenchmarkBudgets::LARGE_N,
                $count / TickBenchmarkBudgets::LARGE_N,
                TickBenchmarkBudgets::MAX_REPEATS_PER_DOMINION,
                $sql,
                $this->formatStatements($profile->duplicateGroups(), 10)
            )
        );
    }

    /**
     * Appends the full profile to a failure message. A budget failure should say
     * what broke it, not just that something did.
     */
    private function explain(TickProfile $profile, string $message): string
    {
        return $message . PHP_EOL . PHP_EOL . $profile->render();
    }

    /**
     * @param array<string, int> $statements
     */
    private function formatStatements(array $statements, int $limit = 5): string
    {
        $lines = [];

        foreach (array_slice($statements, 0, $limit, true) as $sql => $count) {
            $lines[] = sprintf('  %6dx  %s', $count, $sql);
        }

        return $lines === [] ? '  (none)' : implode(PHP_EOL, $lines);
    }
}
