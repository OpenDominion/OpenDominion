<?php

namespace OpenDominion\Tests\Benchmark;

use DB;
use OpenDominion\Services\Dominion\TickService;
use PHPUnit\Framework\Attributes\Group;

/**
 * Phase 2: tests pinned to individual findings from firstroundfindings.txt and
 * secondroundfindings.txt, rather than to aggregate budgets.
 *
 * Two kinds of test live here:
 *
 *  - BUDGETS, which assert a cost stays within bounds. These behave like phase
 *    1: lower the bound when the finding is fixed.
 *
 *  - CHARACTERIZATIONS, which assert that an unfixed finding still behaves the
 *    way it currently does. These exist so the finding is measured rather than
 *    merely described, and so that fixing it produces a loud, specific failure
 *    that points at the test to invert. They are labelled CHARACTERIZATION and
 *    each says what to change when it breaks.
 */
#[Group('benchmark')]
class TickFindingsBenchmarkTest extends AbstractTickBenchmarkTestCase
{
    /**
     * Finding 3.4 - DominionSaved fires inside the tick and duplicates the work.
     *
     * performSpellEffects calls $dominion->update() for the Cull the Weak,
     * Death and Decay and Dark Elf special cases. Every update dispatches
     * DominionSavedEvent, whose listener runs a full networth calculation AND a
     * second precalculateTick - which the main loop then does again.
     *
     * This test pins the SCOPE of that duplication: exactly the dominions
     * carrying one of those spells, and no others. It is the assertion that
     * would have caught the skewed fixture that produced the phase 0 numbers.
     */
    public function testPrecalculateTickRunsOncePerDominionPlusOncePerSpecialSpell(): void
    {
        $fixture = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $tickService = $this->tickService();

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'precalculateTick invocations',
            static function () use ($tickService, $fixture): void {
                $tickService->performTick($fixture->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $invocations = $profile->countMatchingPattern(
            TickBenchmarkBudgets::PRECALCULATE_INVOCATION_PATTERN
        );

        $expected = $fixture->dominionCount() + $fixture->specialSpellCount;

        $this->write(sprintf(
            "  precalculateTick invocations: %d for %d dominions (%d carrying a special spell)%s",
            $invocations,
            $fixture->dominionCount(),
            $fixture->specialSpellCount,
            PHP_EOL
        ));

        $this->assertSame(
            $expected,
            $invocations,
            sprintf(
                "Expected %d precalculateTick calls (one per dominion, plus one more for each of the %d "
                . "carrying a performSpellEffects special-case spell), saw %d.\n\n"
                . "MORE than expected means DominionSaved is now firing for dominions it did not before - "
                . "every extra call is roughly %d wasted queries.\n"
                . "FEWER means finding 3.4 has been fixed; update this test to expect one call per dominion.",
                $expected,
                $fixture->specialSpellCount,
                $invocations,
                TickBenchmarkBudgets::PRECALCULATE_MAX_QUERIES
            )
        );
    }

    /**
     * CHARACTERIZATION - finding M1 of secondroundfindings.txt.
     *
     * Phase A only touches dominions with protection_finished = true
     * (TickService.php:275). Phase B iterates $round->activeDominions(), which is
     * only filtered on locked_at (Round.php:80-83) - so every dominion still in
     * protection pays the full per-dominion cost each hour despite phase A having
     * changed nothing about it.
     *
     * In the opening days of a round those dominions are a large share of N, so
     * this is at its worst exactly when the game is busiest.
     *
     * WHEN M1 IS FIXED: the cost of the half-in-protection round drops to roughly
     * half, this assertion fails, and it should be inverted to assert that
     * protection dominions are excluded.
     */
    public function testProtectionDominionsStillPayFullPhaseBCost(): void
    {
        $half = (int)(TickBenchmarkBudgets::LARGE_N / 2);

        $allActive = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $halfProtected = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N, $half);

        $tickService = $this->tickService();

        $this->warmUp($allActive, $halfProtected);

        $activeProfile = $this->profiler->profile(
            'all active',
            static function () use ($tickService, $allActive): void {
                $tickService->performTick($allActive->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $protectedProfile = $this->profiler->profile(
            'half in protection',
            static function () use ($tickService, $halfProtected): void {
                $tickService->performTick($halfProtected->round);
            },
            TickBenchmarkBudgets::LARGE_N
        );

        $ratio = $protectedProfile->queryCount() / $activeProfile->queryCount();

        $this->write(sprintf(
            "  phase B with %d/%d in protection: %d queries vs %d all-active (%.0f%%)%s",
            $half,
            TickBenchmarkBudgets::LARGE_N,
            $protectedProfile->queryCount(),
            $activeProfile->queryCount(),
            $ratio * 100,
            PHP_EOL
        ));

        $this->assertGreaterThan(
            0.9,
            $ratio,
            sprintf(
                "A round with half its dominions in protection cost %.0f%% of a fully active one (%d vs %d queries).\n\n"
                . "This test CHARACTERIZES finding M1: protection dominions are expected to cost the same as active "
                . "ones today, because phase B does not filter on protection_finished.\n"
                . 'A ratio near 0.5 means M1 has been fixed - invert this assertion to assert the saving.',
                $ratio * 100,
                $protectedProfile->queryCount(),
                $activeProfile->queryCount()
            )
        );
    }

    /**
     * REGRESSION GUARD - finding 3.7, fixed.
     *
     * tickDaily() used to open with Round::with('dominions')->active()->get(),
     * hydrating a full model for every dominion in the round and reading none of
     * them: the body uses $round->realms(), $round->dominions() (a fresh query
     * builder, not the relation) and $round->daysInRound().
     *
     * Query COUNT alone would not catch a reintroduction - it is one statement
     * regardless of N - so this asserts the statement is absent entirely.
     */
    public function testTickDailyDoesNotEagerLoadDominions(): void
    {
        $fixture = $this->seeder->seed(TickBenchmarkBudgets::SMALL_N);
        $tickService = $this->tickService();

        $this->warmUp($fixture);

        $profile = $this->profiler->profile(
            'tickDaily',
            static function () use ($tickService): void {
                $tickService->tickDaily();
            },
            TickBenchmarkBudgets::SMALL_N
        );

        $deadLoads = $profile->countMatchingPattern('/^select `dominions`\.\*.*from `dominions`.*`realms`/is');

        $this->write(sprintf(
            "  tickDaily(): %d queries total, %d of them a dominions eager-load%s",
            $profile->queryCount(),
            $deadLoads,
            PHP_EOL
        ));

        $this->assertSame(
            0,
            $deadLoads,
            "tickDaily() is eager-loading dominions again (finding 3.7). The body reads none of them - "
            . "it uses \$round->realms() and \$round->dominions(), both fresh query builders - so this "
            . "hydrates a model per dominion in the round for nothing.\n\n"
            . $profile->render()
        );
    }

    /**
     * Finding 4.1 - the daily-rankings self-join is quadratic and unindexed.
     *
     * daily_rankings carries only unique(dominion_id, key); there is no index on
     * (round_id, key, value), so the range self-join at TickService.php:1228 has
     * nothing to seek on. With ~20 ranking keys and N dominions the table holds
     * ~20N rows and the join compares on the order of 20 x N^2 row pairs.
     *
     * Reported, not asserted. The cost lives INSIDE one statement, so query
     * counts cannot see it and only timing can - and timing is machine-dependent.
     * A hard budget here would be a flaky test; the number is here to be read
     * before and after the window-function rewrite.
     */
    public function testReportDailyRankingsScaling(): void
    {
        $tickService = $this->tickService();

        // updateDailyRankings() takes no round argument - it loops every round
        // matching Round::activeRankings(). The two measurements must therefore
        // be isolated by taking the first round out of scope before seeding the
        // second, or the large run would re-rank the small round as well and the
        // comparison would measure nothing.
        $small = $this->seeder->seed(TickBenchmarkBudgets::SMALL_N);
        $this->warmUp($small);
        $smallProfile = $this->profileRankings($tickService, 'rankings, small');

        $this->expireRound($small->round->id);

        $large = $this->seeder->seed(TickBenchmarkBudgets::LARGE_N);
        $this->warmUp($large);
        $largeProfile = $this->profileRankings($tickService, 'rankings, large');

        $ratio = $smallProfile > 0 ? $largeProfile / $smallProfile : 0;
        $dataRatio = TickBenchmarkBudgets::LARGE_N / TickBenchmarkBudgets::SMALL_N;

        $this->write(sprintf(
            "\n  daily rankings self-join\n"
            . "    N = %-4d %8.1f ms\n"
            . "    N = %-4d %8.1f ms\n"
            . "    growth   %.2fx for %.2fx the dominions\n"
            . "    (linear would be ~%.2fx; quadratic ~%.2fx)\n\n",
            TickBenchmarkBudgets::SMALL_N,
            $smallProfile,
            TickBenchmarkBudgets::LARGE_N,
            $largeProfile,
            $ratio,
            $dataRatio,
            $dataRatio,
            $dataRatio ** 2
        ));

        $this->assertGreaterThan(0.0, $largeProfile, 'Expected the ranking statements to take measurable time.');
    }

    /**
     * Moves a round's end_date into the past so Round::activeRankings() stops
     * matching it.
     */
    private function expireRound(int $roundId): void
    {
        DB::table('rounds')
            ->where('id', $roundId)
            ->update(['end_date' => now()->subDays(1)]);
    }

    /**
     * Runs updateDailyRankings and returns the total time spent in
     * daily_rankings statements, in milliseconds.
     */
    private function profileRankings(TickService $tickService, string $label): float
    {
        $profile = $this->profiler->profile(
            $label,
            static function () use ($tickService): void {
                $tickService->updateDailyRankings();
            }
        );

        $total = 0.0;

        foreach ($profile->slowestGroups(50) as $sql => $stats) {
            if (stripos($sql, 'daily_rankings') !== false) {
                $total += $stats['totalMs'];
            }
        }

        return $total;
    }
}
