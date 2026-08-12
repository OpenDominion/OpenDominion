<?php

namespace OpenDominion\Tests\Benchmark;

/**
 * Hard budgets for the tick, enforced by TickServiceBenchmarkTest.
 *
 * These are a RATCHET. Every value below was measured, not estimated. When an
 * optimization lands, lower the relevant constant in the same commit - the
 * lowered number is the evidence the fix worked, and it stops the gain being
 * quietly given back later.
 *
 * Raising a constant is a deliberate act that needs justification in the commit
 * message. It means the tick got more expensive on purpose.
 *
 * ---------------------------------------------------------------------------
 * BASELINE: commit d287fd7a1, 2026-08-12
 * Docker (php:8.5-apache, PHP 8.5.9, MariaDB 10.11), PHPUnit 12.5.24
 * See phase0_headline.md for the full measurement.
 * ---------------------------------------------------------------------------
 *
 * On tolerances. Query counts are near-deterministic but not perfectly so: over
 * three baseline runs, N=40 returned 2358 every time while N=10 returned 611,
 * 611 and 610. The fixture's row counts also drift slightly because
 * createNonPlayer calls random_chance(), which is backed by random_int and
 * cannot be seeded (see BenchmarkRoundSeeder::withDeterministicRandomness).
 *
 * So the tolerances below are sized against MEASURED noise, not guessed:
 * observed noise is around 1 query in 2358 (0.04%), while any real regression -
 * a relation no longer eager-loaded, a lookup pulled back into the loop - costs
 * at least one query per dominion, i.e. 40 queries at N=40. The gap between
 * those two magnitudes is three orders of magnitude, so a tolerance of a
 * fraction of a query per dominion is both noise-proof and regression-tight.
 */
final class TickBenchmarkBudgets
{
    /**
     * Dominion counts the scaling assertions run at. Small enough that the suite
     * stays usable, far enough apart that the slope means something.
     */
    public const SMALL_N = 10;

    public const LARGE_N = 40;

    /**
     * TickService::precalculateTick() for a single dominion.
     *
     * Measured: 19. Finding 2.1 of firstroundfindings.txt predicted "~19-20"
     * from static analysis; this is that prediction confirmed exactly.
     *
     * Single dominion, single code path, no variance observed across runs, so
     * this one is held at the measured value with no tolerance.
     */
    public const PRECALCULATE_MAX_QUERIES = 19;

    /**
     * TickService::performTick($round, $dominion) - the single-dominion branch
     * used by the manual advance-tick path (MiscController.php:404) and by NPD
     * generation.
     *
     * Measured: 91, stable across runs.
     *
     * This is HIGHER than the 77 recorded in phase0_headline.md, and the
     * difference is real rather than noise. The single-dominion branch skips the
     * eager-load at TickService.php:395-409 and does `collect([$dominion])`, so
     * it inherits whatever relation state the CALLER's model happens to carry.
     * precalculateTick then calls loadMissing() for race, race.perks, race.units
     * and race.units.perks - free if the caller already hydrated them, 14 extra
     * queries if not.
     *
     * The phase 0 report measured 77 because it profiled precalculateTick on the
     * same model instance immediately beforehand, which left those relations
     * warm. This budget deliberately holds the COLD number: it is the worst
     * case, it is what a caller coming from a fresh fetch pays, and it is the
     * shape that stays stable under test reordering.
     */
    public const SINGLE_DOMINION_TICK_MAX_QUERIES = 91;

    /**
     * Queries per dominion for a whole-round performTick() at LARGE_N.
     *
     * Measured: 58.95 (2358 queries / 40 dominions).
     *
     * Note this is THREE TIMES the precalculateTick figure above. Both findings
     * reports anchored on precalculateTick and neither costed the rest of phase
     * B; the loop's real per-dominion price is this number.
     *
     * Tolerance: 0.55 queries/dominion (22 queries at N=40). Twenty times the
     * observed noise, and still fails on a regression of one query per dominion.
     */
    public const PER_DOMINION_MAX_QUERIES = 59.5;

    /**
     * Marginal queries per additional dominion, from the SMALL_N -> LARGE_N
     * slope. This is what catches accidental superlinearity: it is a ratio of
     * two measurements, so it carries more noise than either alone.
     *
     * Measured: 58.23 and 58.27 across runs. Budget is measured x 1.15.
     */
    public const MAX_MARGINAL_QUERIES = 67.0;

    /**
     * Phase A - the bulk join-updates against dominions, dominion_spells and
     * dominion_queue at TickService.php:284-380.
     *
     * This is the part of the tick that is already right: three statements for
     * the entire round, regardless of N. The budget exists to keep it that way,
     * not because it is currently a problem.
     */
    public const PHASE_A_STATEMENTS = 3;

    /**
     * How many times any single statement may execute, per dominion.
     *
     * Measured: 6 - the spell_perk_types pivot select, which is both the most
     * repeated statement and the slowest by total time (85 ms of 240 executions
     * at N=40). That is the unmemoized perk rebuild of finding 2.3 surfacing as
     * real queries, because $spell->perks is lazily loaded.
     *
     * Lower this to 3 when finding 2.2 (the lazy ->spell in cleanupActiveSpells)
     * is fixed, and toward 1 when perk memoization lands.
     */
    public const MAX_REPEATS_PER_DOMINION = 6;

    /**
     * Matches only phase A's three join-updates. Laravel's MySQL grammar emits
     * the join immediately after the table name on UPDATE, which distinguishes
     * these from the per-dominion `update dominion_tick set ...` issued by
     * $tick->save().
     */
    public const PHASE_A_PATTERN = '/^update\s+`(?:dominions|dominion_spells|dominion_queue)`\s+inner\s+join/i';
}
