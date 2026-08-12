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
 * BASELINE: 2026-08-12, after the phase 2 fixture correction
 * Docker (php:8.5-apache, PHP 8.5.9, MariaDB 10.11), PHPUnit 12.5.24
 * ---------------------------------------------------------------------------
 *
 * These numbers REPLACE the ones recorded in phase0_headline.md, which were
 * measured against a skewed fixture. BenchmarkRoundSeeder picked the spells
 * carrying the most perks, and death_and_decay - which holds
 * convert_peasants_to_self_military_unit1 - is among them. That put a
 * performSpellEffects special case on every single dominion, so every dominion
 * took a Dominion::update(), fired DominionSaved, and paid for a SECOND
 * precalculateTick. Per-dominion cost read 58.95 where the common case is 31.57.
 *
 * The fixture now applies those spells to SPECIAL_SPELL_RATIO of dominions
 * instead. Finding 3.4 of firstroundfindings.txt was right as written - the
 * double precalculation is confined to affected dominions - and the corrected
 * measurement puts a number on it: roughly 27 extra queries each.
 *
 * On tolerances. With the fixture corrected the counts are fully stable: 19, 61,
 * 336 (N=10) and 1263 (N=40) reproduced exactly across runs, where the previous
 * fixture drifted by a query at N=10. Tolerances are still sized against the
 * gap between noise and a real regression: any regression worth catching costs
 * at least one query per dominion, i.e. 40 queries at N=40, so a fraction of a
 * query per dominion is both noise-proof and regression-tight.
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
     * Single dominion, single code path, no variance across runs, so held at the
     * measured value with no tolerance.
     */
    public const PRECALCULATE_MAX_QUERIES = 19;

    /**
     * TickService::performTick($round, $dominion) - the single-dominion branch
     * used by the manual advance-tick path (MiscController.php:404) and by NPD
     * generation.
     *
     * Measured: 60-61 across runs. Budget is one above the highest observed,
     * because this is the only measurement that still drifts by a query - the
     * whole-round numbers are exact. A regression that adds a single query to
     * just this path is far less significant than one that adds a query per
     * dominion, which PER_DOMINION_MAX_QUERIES catches tightly.
     *
     * This branch skips the eager-load at TickService.php:395-409 and does
     * `collect([$dominion])`, so it inherits whatever relation state the CALLER's
     * model carries. precalculateTick then calls loadMissing() for race,
     * race.perks, race.units and race.units.perks - free if the caller already
     * hydrated them, ~15 extra queries if not. This budget holds the COLD
     * number: it is the worst case, and it is stable under test reordering.
     *
     * TickBaselineReportTest reports a lower figure for the same call because it
     * profiles precalculateTick on the same instance first, leaving the
     * relations warm. That is also a real production shape - NPD generation does
     * exactly that at TickService.php:241-242.
     */
    public const SINGLE_DOMINION_TICK_MAX_QUERIES = 62;

    /**
     * Queries per dominion for a whole-round performTick() at LARGE_N.
     *
     * Measured: 31.57 (1263 queries / 40 dominions).
     *
     * Still well above the 19 that precalculateTick alone costs. Both findings
     * reports anchored on precalculateTick and neither costed the rest of phase
     * B; the loop's real per-dominion price is this number.
     *
     * Tolerance: ~0.5 queries/dominion (about 20 queries at N=40), which fails on
     * a regression of one query per dominion.
     */
    public const PER_DOMINION_MAX_QUERIES = 32.1;

    /**
     * Marginal queries per additional dominion, from the SMALL_N -> LARGE_N
     * slope. This is what catches accidental superlinearity: it is a ratio of two
     * measurements, so it carries more noise than either alone.
     *
     * Measured: 30.90. Budget is measured x 1.15.
     */
    public const MAX_MARGINAL_QUERIES = 35.5;

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
     * Measured: 6.10 (244 executions at N=40) - the spell_perk_types pivot
     * select, which is both the most repeated statement and the slowest by total
     * time. That is finding 2.3's unmemoized perk rebuilds surfacing as real
     * queries, because $spell->perks is lazily loaded.
     *
     * The .10 above a flat 6 is the three per-dominion N+1s inside
     * performSpellEffects (finding 3.5), paid only by the SPECIAL_SPELL_RATIO of
     * dominions carrying those spells.
     *
     * Lower toward 3 when finding 2.2 (the lazy ->spell in cleanupActiveSpells)
     * is fixed, and toward 1 when perk memoization lands.
     */
    public const MAX_REPEATS_PER_DOMINION = 6.25;

    /**
     * Matches only phase A's three join-updates. Laravel's MySQL grammar emits
     * the join immediately after the table name on UPDATE, which distinguishes
     * these from the per-dominion `update dominion_tick set ...` issued by
     * $tick->save().
     */
    public const PHASE_A_PATTERN = '/^update\s+`(?:dominions|dominion_spells|dominion_queue)`\s+inner\s+join/i';

    /**
     * Matches the Tick::firstOrCreate lookup at the top of precalculateTick, and
     * nothing else - the eager load of the tick relation uses `IN (...)`, and
     * $tick->save() is an UPDATE. Counting these counts precalculateTick calls.
     */
    public const PRECALCULATE_INVOCATION_PATTERN = '/^select \* from `dominion_tick` where \(`dominion_id` = \?\)/i';
}
