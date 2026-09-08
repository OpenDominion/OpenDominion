<?php

namespace OpenDominion\Tests\Unit\Services\Dominion;

use DB;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractTestCase;

/**
 * Pins the rank values produced by TickService::updateDailyRankings().
 *
 * Nothing asserted these before, and they are load-bearing: ValorCalculator's
 * getFixedValorLandRank() is non-linear in the rank integer (1000 for rank 1,
 * 500 for rank 2, round(1250/rank)-5 beyond), and RaidCalculator,
 * RankingsService, ValhallaController and MessageBoardController all branch on
 * rank == 1 - where a tie currently means every tied leader wins.
 *
 * These tests were written against the original COUNT(b.value)+1 self-join and
 * must keep passing UNCHANGED through any rewrite of it. If one needs editing,
 * the rewrite changed behaviour.
 *
 * Note updateDailyRankings() takes no round argument: it processes every round
 * matching Round::activeRankings() whose start_date hour equals the current
 * hour. Every assertion here is therefore scoped to the ids this test created,
 * so leftover rounds in a shared dev database cannot affect the result.
 */
class DailyRankingsTest extends AbstractTestCase
{
    private TickService $tickService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tickService = $this->app->make(TickService::class);
    }

    /**
     * The distinguishing test. RANK() shares a rank across ties and then skips,
     * DENSE_RANK() would not skip, and ROW_NUMBER() would not tie at all.
     */
    public function testTiesShareARankAndCreateAGap(): void
    {
        $round = $this->createRankableRound();
        [$first, $tied, $third, $fourth] = $this->createDominionsWithPrestige($round, [300, 300, 200, 100]);

        $this->tickService->updateDailyRankings();

        $ranks = $this->storedRanks($round, 'prestige');

        $this->assertSame(1, $ranks[$first->id]);
        $this->assertSame(1, $ranks[$tied->id]);
        $this->assertSame(3, $ranks[$third->id], 'The value after a two-way tie must skip to rank 3.');
        $this->assertSame(4, $ranks[$fourth->id]);

        $this->assertNotContains(2, $ranks, 'A tie at rank 1 must leave rank 2 unused.');
    }

    public function testPreviousRankIsNullOnTheFirstRun(): void
    {
        $round = $this->createRankableRound();
        [$dominion] = $this->createDominionsWithPrestige($round, [300]);

        $this->tickService->updateDailyRankings();

        $row = $this->rankingRow($dominion, 'prestige');

        $this->assertSame(1, (int)$row->rank);
        $this->assertNull($row->previous_rank);
    }

    /**
     * previous_rank must carry the rank as it stood BEFORE this run. This is
     * what breaks if a rewrite assigns it from the live column inside a
     * multi-table UPDATE, where assignment order is not guaranteed.
     */
    public function testPreviousRankCarriesTheRankFromTheRunBefore(): void
    {
        $round = $this->createRankableRound();
        [$alpha, $beta] = $this->createDominionsWithPrestige($round, [300, 100]);

        $this->tickService->updateDailyRankings();

        $this->assertSame(1, (int)$this->rankingRow($alpha, 'prestige')->rank);
        $this->assertSame(2, (int)$this->rankingRow($beta, 'prestige')->rank);

        // Swap their standing and rank again.
        $this->setPrestige($alpha, 100);
        $this->setPrestige($beta, 300);

        $this->tickService->updateDailyRankings();

        $alphaRow = $this->rankingRow($alpha, 'prestige');
        $betaRow = $this->rankingRow($beta, 'prestige');

        $this->assertSame(2, (int)$alphaRow->rank);
        $this->assertSame(1, (int)$alphaRow->previous_rank);

        $this->assertSame(1, (int)$betaRow->rank);
        $this->assertSame(2, (int)$betaRow->previous_rank);
    }

    /**
     * The ranking window partitions by key only - the round is a filter. This
     * is the test that catches dropping that filter from either side of a
     * rewritten statement.
     */
    public function testRanksAreScopedToTheirOwnRound(): void
    {
        $roundOne = $this->createRankableRound('-7 days');
        $roundTwo = $this->createRankableRound('-3 days');

        [$smallFirst, $smallSecond] = $this->createDominionsWithPrestige($roundOne, [100, 50]);
        [$bigFirst, $bigSecond] = $this->createDominionsWithPrestige($roundTwo, [10000, 9000]);

        $this->tickService->updateDailyRankings();

        $roundOneRanks = $this->storedRanks($roundOne, 'prestige');
        $roundTwoRanks = $this->storedRanks($roundTwo, 'prestige');

        $this->assertSame(1, $roundOneRanks[$smallFirst->id], 'A far larger dominion in another round must not outrank this one.');
        $this->assertSame(2, $roundOneRanks[$smallSecond->id]);

        $this->assertSame(1, $roundTwoRanks[$bigFirst->id]);
        $this->assertSame(2, $roundTwoRanks[$bigSecond->id]);
    }

    /**
     * Catches a dropped PARTITION BY key: the same dominions must rank
     * independently under each ranking key.
     */
    public function testRanksArePerKeyIndependent(): void
    {
        $round = $this->createRankableRound();
        [$one, $two, $three] = $this->createDominionsWithPrestige($round, [300, 200, 100]);

        // Invert the land ordering relative to prestige.
        $this->setLand($one, 100);
        $this->setLand($two, 500);
        $this->setLand($three, 900);

        $this->tickService->updateDailyRankings();

        $prestigeRanks = $this->storedRanks($round, 'prestige');
        $landRanks = $this->storedRanks($round, 'largest-dominions');

        $this->assertSame([1, 2, 3], [$prestigeRanks[$one->id], $prestigeRanks[$two->id], $prestigeRanks[$three->id]]);
        $this->assertSame([3, 2, 1], [$landRanks[$one->id], $landRanks[$two->id], $landRanks[$three->id]]);
    }

    /**
     * A locked dominion has its value forced to zero but still receives a row
     * and a rank, placing it last rather than removing it from the ladder.
     */
    public function testZeroedOutDominionsAreStillRanked(): void
    {
        $round = $this->createRankableRound();
        [$active, $locked] = $this->createDominionsWithPrestige($round, [300, 500]);

        DB::table('dominions')->where('id', $locked->id)->update(['locked_at' => now()]);

        $this->tickService->updateDailyRankings();

        $lockedRow = $this->rankingRow($locked, 'prestige');

        $this->assertSame(0, (int)$lockedRow->value, 'A locked dominion must have its value zeroed.');
        $this->assertSame(2, (int)$lockedRow->rank, 'It must still be ranked, behind the active dominion.');
        $this->assertSame(1, (int)$this->rankingRow($active, 'prestige')->rank);
    }

    /**
     * The equivalence proof: every stored rank must match what the original
     * self-join computes from the same stored values, across every key, with a
     * spread chosen to produce heavy ties.
     */
    public function testStoredRanksMatchTheLegacySelfJoinAcrossEveryKey(): void
    {
        $round = $this->createRankableRound();
        $this->createDominionsWithPrestige($round, [500, 500, 500, 250, 250, 100, 1]);

        $this->tickService->updateDailyRankings();

        $expected = $this->legacyRanks($round);
        $actual = $this->storedRanksByKey($round);

        $this->assertNotEmpty($expected, 'Expected the round to have produced ranking rows.');
        $this->assertEquals($expected, $actual);
    }

    /**
     * A round whose start_date hour matches the current hour, which is what
     * updateDailyRankings() gates on.
     */
    private function createRankableRound(string $startDate = '-7 days'): Round
    {
        return $this->createRound($startDate);
    }

    /**
     * @param array<int, int> $prestigeValues
     * @return array<int, Dominion>
     */
    private function createDominionsWithPrestige(Round $round, array $prestigeValues): array
    {
        $dominions = [];

        foreach ($prestigeValues as $prestige) {
            $dominion = $this->createDominion($this->createUser(), $round);
            $this->setPrestige($dominion, $prestige);
            $dominions[] = $dominion;
        }

        return $dominions;
    }

    /**
     * Written straight to the database rather than through the model, so the
     * DominionSaved listener does not run a networth calculation and a tick
     * pre-calculation for every fixture row.
     */
    private function setPrestige(Dominion $dominion, int $prestige): void
    {
        DB::table('dominions')->where('id', $dominion->id)->update(['prestige' => $prestige]);
    }

    private function setLand(Dominion $dominion, int $landPlain): void
    {
        DB::table('dominions')->where('id', $dominion->id)->update(['land_plain' => $landPlain]);
    }

    private function rankingRow(Dominion $dominion, string $key): object
    {
        $row = DB::table('daily_rankings')
            ->where('dominion_id', $dominion->id)
            ->where('key', $key)
            ->first();

        $this->assertNotNull($row, "Expected a '{$key}' ranking row for dominion {$dominion->id}.");

        return $row;
    }

    /**
     * @return array<int, int> dominion id => rank
     */
    private function storedRanks(Round $round, string $key): array
    {
        return DB::table('daily_rankings')
            ->where('round_id', $round->id)
            ->where('key', $key)
            ->get(['dominion_id', 'rank'])
            ->mapWithKeys(static function (object $row): array {
                return [(int)$row->dominion_id => (int)$row->rank];
            })
            ->all();
    }

    /**
     * @return array<string, int> "dominionId:key" => rank
     */
    private function storedRanksByKey(Round $round): array
    {
        $ranks = DB::table('daily_rankings')
            ->where('round_id', $round->id)
            ->get(['dominion_id', 'key', 'rank'])
            ->mapWithKeys(static function (object $row): array {
                return ["{$row->dominion_id}:{$row->key}" => (int)$row->rank];
            })
            ->all();

        ksort($ranks);

        return $ranks;
    }

    /**
     * The pre-rewrite ranking expression, kept verbatim as the reference
     * implementation. Do not "modernise" this - its whole value is being the
     * thing the new implementation must agree with.
     *
     * @return array<string, int> "dominionId:key" => rank
     */
    private function legacyRanks(Round $round): array
    {
        $ranks = DB::table('daily_rankings AS a')
            ->select(DB::raw('a.dominion_id, a.`key` AS ranking_key, COUNT(b.value)+1 AS legacy_rank'))
            ->leftJoin('daily_rankings AS b', function ($join) use ($round) {
                $join->on('a.value', '<', 'b.value');
                $join->on('a.key', '=', 'b.key');
                $join->where('b.round_id', $round->id);
            })
            ->where('a.round_id', $round->id)
            ->groupBy('a.dominion_id', 'a.key', 'a.value')
            ->get()
            ->mapWithKeys(static function (object $row): array {
                return ["{$row->dominion_id}:{$row->ranking_key}" => (int)$row->legacy_rank];
            })
            ->all();

        ksort($ranks);

        return $ranks;
    }
}
