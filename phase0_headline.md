# Tick Benchmark — Phase 0 Headline Numbers

> **⚠ SUPERSEDED — the per-dominion figures below are inflated by roughly 2x.**
>
> These were measured against a skewed fixture. `BenchmarkRoundSeeder` picked the
> spells carrying the most perks, and `death_and_decay` — which holds
> `convert_peasants_to_self_military_unit1` — is among them. That put a
> `performSpellEffects` special case on **every** dominion, so every dominion
> took a `Dominion::update()`, fired `DominionSaved`, and paid for a second
> `precalculateTick`.
>
> Corrected in phase 2. Per-dominion cost is **31.57**, not 58.95; marginal cost
> is **30.90**, not 58.23. See `TickBenchmarkBudgets` for the current numbers.
>
> Two conclusions below are wrong as written: the claim that `precalculateTick`
> runs 2–3x for *every* dominion (it runs once per dominion plus once per
> dominion carrying one of three special spells — 44 calls for 40 dominions), and
> the ~87,000 query / ~150 s extrapolation to N=1500, which halves.
>
> What still holds: the 19-query `precalculateTick` figure, the 74–76% PHP share,
> the linear scaling, and every N+1 identified.

Baseline measurement of `TickService` before any optimization work.

- **Date:** 2026-08-12
- **Commit:** `d287fd7a1` (branch `feature/tick-performance`)
- **Harness:** `tests/Benchmark/TickBaselineReportTest.php`, run with `./vendor/bin/phpunit --group=benchmark`
- **Environment:** Docker (`php:8.5-apache`, PHP 8.5.9, MariaDB 10.11), PHPUnit 12.5.24

## Headline

| Measurement | Queries | Wall time | Share in PHP |
|---|---|---|---|
| `precalculateTick()`, one dominion | **19** | 38.9 ms | 80% |
| `performTick($round, $dominion)`, single-dominion path | **77** [^1] | 129.5 ms | 76% |
| `performTick($round)`, N = 10 | **611** (61.10/dom) | 994.5 ms | 75% |
| `performTick($round)`, N = 40 | **2358** (58.95/dom) | 3973.8 ms | 74% |

[^1]: This figure is measurement-order dependent, and phase 1 measures **91**
    for the same call. The single-dominion branch skips the eager-load at
    `TickService.php:395-409`, so it inherits whatever relation state the
    caller's model carries; `precalculateTick`'s `loadMissing()` for the race
    relations then costs 0 or 14 queries accordingly. The run above profiled
    `precalculateTick` on the same instance immediately beforehand, leaving them
    warm. `TickBenchmarkBudgets::SINGLE_DOMINION_TICK_MAX_QUERIES` holds the cold
    number, 91, as the worst case.

## Scaling (N = 10 → 40)

| | |
|---|---|
| Marginal cost | **58.23 queries per additional dominion** |
| Fixed overhead | 28.7 queries per round |
| Wall time growth | 4.00× for 4.00× the dominions |

Linear at this scale — Phase A's bulk update is constant regardless of N. The
cost is a large per-dominion constant, not bad scaling.

Extrapolating the same shape to N = 1500: roughly 87,000 queries and ~150 s.

## Most repeated statements (N = 40)

| Executions | Per dominion | Statement |
|---|---|---|
| 240× | 6 | `spell_perk_types` pivot select (also slowest by total time, 85.2 ms) |
| 120× | 3 | `select * from dominions where id = ? limit 1` |
| 120× | 3 | `select * from spells where id = ? limit 1` |
| 84× | ~2 | `unit_perk_types` pivot select |
| 80× | 2 | `select * from dominion_tick where dominion_id = ? limit 1` |
| 80× | 2 | `select spell_id from dominion_spells where dominion_id = ? and duration <= ?` |

## Caveats

- Query counts are deterministic and machine-independent; **wall times are not**
  and must never be asserted on.
- Tests run under `DatabaseTransactions`, so the tick's own `DB::transaction`
  calls degrade to savepoints. Commit cost and lock contention are **not**
  measured — finding 5.2 needs a real-scale profile run.
- Reference-data caches (`Race::findWithRelationsCached`, `Round::findCached`)
  are warmed before measurement, hiding roughly one query per distinct race plus
  one per round. That is fixed cost, not per-dominion cost.
- N = 10/40 against a production N in the thousands. Linearity is established by
  the slope between the two, not by absolute magnitude.
