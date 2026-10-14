<?php

namespace OpenDominion\Tests\Unit\Calculators;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\ValorCalculator;
use OpenDominion\Models\DailyRanking;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\Valor;
use OpenDominion\Services\QueryProfilerService;
use OpenDominion\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Pins ValorCalculator's fixed and bonus valor before those methods are
 * de-quadratified.
 *
 * calculateFixedValor() currently loads every daily_rankings row for the round
 * and then filters that collection once per dominion; calculateBonusValor()
 * does the same over the valor table. Both are being rewritten to aggregate in
 * SQL, so these tests exist to prove the numbers do not move.
 *
 * The subtle one is testRealmZeroDominionScoresNothingForRankConqueredAndBounties:
 * calculateFixedValor and calculateBreakdown look like twins but are not, and
 * the difference is easy to erase by accident.
 */
#[CoversClass(ValorCalculator::class)]
class ValorCalculatorTest extends AbstractTestCase
{
    private ValorCalculator $calculator;

    private LandCalculator $landCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = $this->app->make(ValorCalculator::class);
        $this->landCalculator = $this->app->make(LandCalculator::class);
    }

    public function testFixedValorCombinesRankLandConqueredAndBounties(): void
    {
        $round = $this->createRound('-7 days');

        $first = $this->createDominion($this->createUser(), $round);
        $second = $this->createDominion($this->createUser(), $round);

        $this->seedRanking($first, 'largest-dominions', 900, 1);
        $this->seedRanking($second, 'largest-dominions', 300, 2);
        $this->seedRanking($first, 'total-land-conquered', 75, 1);
        $this->seedRanking($second, 'total-land-conquered', 25, 2);
        $this->seedRanking($first, 'bounties-collected', 40, 1);
        $this->seedRanking($second, 'bounties-collected', 10, 2);

        $valor = $this->calculator->calculateFixedValor($round, $this->collect([$first, $second]));

        $totalLand = 1200;
        $expectedFirst = 1000.0
            + round(3000 / $totalLand * $this->landCalculator->getTotalLand($first), 2)
            + round(1500 / 100 * 75, 2)
            + round(1500 / 50 * 40, 2);
        $expectedSecond = 500.0
            + round(3000 / $totalLand * $this->landCalculator->getTotalLand($second), 2)
            + round(1500 / 100 * 25, 2)
            + round(1500 / 50 * 10, 2);

        $this->assertEqualsWithDelta($expectedFirst, $valor[$first->id], 0.01);
        $this->assertEqualsWithDelta($expectedSecond, $valor[$second->id], 0.01);
    }

    /**
     * Rank feeds a non-linear step function, so this pins the exact shape:
     * 1000 for rank 1, 500 for rank 2, then round(1250/rank)-5.
     */
    public function testLandRankValorFollowsTheFixedStepFunction(): void
    {
        $round = $this->createRound('-7 days');

        $expectations = [1 => 1000.0, 2 => 500.0, 3 => 412.0, 10 => 120.0];
        $dominions = [];

        foreach ($expectations as $rank => $expected) {
            $dominion = $this->createDominion($this->createUser(), $round);
            $this->seedRanking($dominion, 'largest-dominions', 1000 - $rank, $rank);
            $dominions[$rank] = $dominion;
        }

        $valor = $this->calculator->calculateFixedValor($round, $this->collect(array_values($dominions)));

        foreach ($expectations as $rank => $expectedLandRankValor) {
            // Only the land-rank component is asserted, so subtract the
            // total-land share, which every dominion also earns.
            $totalLand = array_sum(array_map(fn (int $r): int => 1000 - $r, array_keys($expectations)));
            $totalLandValor = round(3000 / $totalLand * $this->landCalculator->getTotalLand($dominions[$rank]), 2);

            $this->assertEqualsWithDelta(
                $expectedLandRankValor + $totalLandValor,
                $valor[$dominions[$rank]->id],
                0.01,
                "Rank {$rank} should be worth {$expectedLandRankValor} land-rank valor."
            );
        }
    }

    /**
     * calculateFixedValor filters its ranking rows with realm_number != 0 and
     * uses that same filtered set for the per-dominion lookups, so a dominion
     * in the graveyard realm scores nothing for rank, conquered or bounties -
     * while still earning its share of total land, which is read from the
     * dominion itself rather than from a ranking row.
     *
     * calculateBreakdown() deliberately does NOT filter its per-dominion fetch
     * this way. Do not "align" the two methods; this test is what catches it.
     */
    public function testRealmZeroRankingRowsAreIgnoredForTheirOwnDominion(): void
    {
        $round = $this->createRound('-7 days');

        // The filter keys off the ranking row's realm_number, so the row is
        // what needs to carry 0 - no graveyard realm required.
        $buried = $this->createDominion($this->createUser(), $round);
        $living = $this->createDominion($this->createUser(), $round);

        $this->seedRanking($buried, 'largest-dominions', 5000, 1, 0);
        $this->seedRanking($buried, 'total-land-conquered', 500, 1, 0);
        $this->seedRanking($buried, 'bounties-collected', 500, 1, 0);
        $this->seedRanking($living, 'largest-dominions', 100, 2);
        $this->seedRanking($living, 'total-land-conquered', 50, 2);
        $this->seedRanking($living, 'bounties-collected', 20, 2);

        $valor = $this->calculator->calculateFixedValor($round, $this->collect([$buried, $living]));

        // Only the total-land component survives, because that one is read
        // from the dominion itself rather than from a ranking row. The
        // denominator is 100 - the realm-0 row is excluded from it too.
        $expectedBuried = round(3000 / 100 * $this->landCalculator->getTotalLand($buried), 2);

        $this->assertEqualsWithDelta($expectedBuried, $valor[$buried->id], 0.01);
    }

    /**
     * The realm_number != 0 filter also keeps graveyard rows out of the
     * denominators, so a huge buried total must not dilute everyone else.
     */
    public function testRealmZeroRowsAreExcludedFromTheTotals(): void
    {
        $round = $this->createRound('-7 days');

        $buried = $this->createDominion($this->createUser(), $round);
        $living = $this->createDominion($this->createUser(), $round);

        $this->seedRanking($living, 'total-land-conquered', 100, 1);
        $valorWithoutGraveyard = $this->calculator->calculateFixedValor($round, $this->collect([$living]));

        $this->seedRanking($buried, 'total-land-conquered', 900, 2, 0);
        $valorWithGraveyard = $this->calculator->calculateFixedValor($round, $this->collect([$living]));

        $this->assertEqualsWithDelta(
            $valorWithoutGraveyard[$living->id],
            $valorWithGraveyard[$living->id],
            0.01,
            'A graveyard row must not enter the total-land-conquered denominator.'
        );
    }

    public function testMissingAndNullRanksScoreZeroLandRankValor(): void
    {
        $round = $this->createRound('-7 days');

        $nullRank = $this->createDominion($this->createUser(), $round);
        $noRow = $this->createDominion($this->createUser(), $round);

        $this->seedRanking($nullRank, 'largest-dominions', 100, null);

        $valor = $this->calculator->calculateFixedValor($round, $this->collect([$nullRank, $noRow]));

        // A NULL rank scores zero land-rank valor rather than throwing, and
        // both dominions still earn the total-land share - that component is
        // computed from the dominion's own acreage, not from a ranking row.
        $expectedNullRank = round(3000 / 100 * $this->landCalculator->getTotalLand($nullRank), 2);
        $expectedNoRow = round(3000 / 100 * $this->landCalculator->getTotalLand($noRow), 2);

        $this->assertEqualsWithDelta($expectedNullRank, $valor[$nullRank->id], 0.01);
        $this->assertEqualsWithDelta($expectedNoRow, $valor[$noRow->id], 0.01);
    }

    public function testBonusValorSumsEveryValorSourceForTheDominion(): void
    {
        $round = $this->createRound('-7 days');

        $dominion = $this->createDominion($this->createUser(), $round);
        $other = $this->createDominion($this->createUser(), $round);

        $this->seedValor($dominion, 'war_hit', 120.5);
        $this->seedValor($dominion, 'largest_hit', 80.0);
        $this->seedValor($dominion, 'wonder', 40.25);
        $this->seedValor($other, 'war_hit', 999.0);

        $bonus = $this->calculator->calculateBonusValor($round, $this->collect([$dominion, $other]));

        $this->assertEqualsWithDelta(240.75, $bonus[$dominion->id], 0.01);
        $this->assertEqualsWithDelta(999.0, $bonus[$other->id], 0.01);
    }

    public function testBonusValorIsZeroWithoutValorRows(): void
    {
        $round = $this->createRound('-7 days');
        $dominion = $this->createDominion($this->createUser(), $round);

        $bonus = $this->calculator->calculateBonusValor($round, $this->collect([$dominion]));

        $this->assertEqualsWithDelta(0.0, $bonus[$dominion->id], 0.01);
    }

    /**
     * The assertion that actually locks the optimisation in. A future refactor
     * reintroducing a per-dominion query fails here rather than silently at a
     * thousand dominions.
     */
    /**
     * The invariant is that the query count is CONSTANT in the number of
     * dominions, not that it equals any particular number - aggregating the
     * totals separately legitimately changes one query into two. Asserting the
     * absolute would just pin whichever implementation happened to be current.
     */
    public function testFixedValorQueryCountDoesNotGrowWithDominionCount(): void
    {
        $round = $this->createRound('-7 days');

        $few = $this->buildRankedDominions($round, 2);
        $withFew = $this->countRankingQueries($round, $few);

        $many = $this->buildRankedDominions($round, 12);
        $withMany = $this->countRankingQueries($round, $many);

        $this->assertSame(
            $withFew,
            $withMany,
            'calculateFixedValor must issue the same number of daily_rankings queries for 2 dominions as for 12.'
        );
        $this->assertLessThan(5, $withMany, 'Constant, but it should also be a small constant.');
    }

    public function testBonusValorQueryCountDoesNotGrowWithDominionCount(): void
    {
        $round = $this->createRound('-7 days');

        $few = $this->buildRankedDominions($round, 2);
        $withFew = $this->countValorQueries($round, $few);

        $many = $this->buildRankedDominions($round, 12);
        $withMany = $this->countValorQueries($round, $many);

        $this->assertSame(
            $withFew,
            $withMany,
            'calculateBonusValor must issue the same number of valor queries for 2 dominions as for 12.'
        );
        $this->assertLessThan(5, $withMany, 'Constant, but it should also be a small constant.');
    }

    /**
     * @param array<int, Dominion> $dominions
     */
    private function collect(array $dominions): EloquentCollection
    {
        return new EloquentCollection($dominions);
    }

    private function buildRankedDominions(Round $round, int $count): EloquentCollection
    {
        $dominions = [];

        for ($i = 0; $i < $count; $i++) {
            $dominion = $this->createDominion($this->createUser(), $round);
            $this->seedRanking($dominion, 'largest-dominions', 1000 - $i, $i + 1);
            $this->seedValor($dominion, 'war_hit', 10.0);
            $dominions[] = $dominion;
        }

        return $this->collect($dominions);
    }

    private function countRankingQueries(Round $round, EloquentCollection $dominions): int
    {
        return $this->countQueriesMatching('daily_rankings', function () use ($round, $dominions): void {
            $this->calculator->calculateFixedValor($round, $dominions);
        });
    }

    private function countValorQueries(Round $round, EloquentCollection $dominions): int
    {
        return $this->countQueriesMatching('`valor`', function () use ($round, $dominions): void {
            $this->calculator->calculateBonusValor($round, $dominions);
        });
    }

    /**
     * Reuses the profiler that backs the benchmark suite and dev:tick:profile.
     */
    private function countQueriesMatching(string $fragment, callable $callback): int
    {
        $profiler = new QueryProfilerService();

        $profiler->start();
        $callback();
        $queries = $profiler->stop();

        $matches = 0;

        foreach ($queries as $query) {
            if (stripos($query['sql'], $fragment) !== false) {
                $matches++;
            }
        }

        return $matches;
    }

    private function seedRanking(Dominion $dominion, string $key, int $value, ?int $rank, ?int $realmNumber = null): void
    {
        DailyRanking::create([
            'round_id' => $dominion->round_id,
            'dominion_id' => $dominion->id,
            'dominion_name' => $dominion->name,
            'race_name' => $dominion->race->name,
            'realm_number' => $realmNumber ?? $dominion->realm->number,
            'realm_name' => $dominion->realm->name,
            'key' => $key,
            'value' => $value,
            'rank' => $rank,
        ]);
    }

    private function seedValor(Dominion $dominion, string $source, float $amount): void
    {
        Valor::create([
            'round_id' => $dominion->round_id,
            'realm_id' => $dominion->realm_id,
            'dominion_id' => $dominion->id,
            'source' => $source,
            'amount' => $amount,
        ]);
    }
}
