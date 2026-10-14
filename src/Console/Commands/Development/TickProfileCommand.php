<?php

namespace OpenDominion\Console\Commands\Development;

use DB;
use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Services\QueryProfilerService;
use RuntimeException;
use Throwable;

/**
 * Profiles a real tick at production scale.
 *
 * The PHPUnit benchmarks in tests/Benchmark cap out around 40 dominions and run
 * inside a wrapping transaction, which turns the tick's own DB::transaction
 * calls into savepoints. That hides two things the findings reports care about:
 *
 *   - Finding 4.1, the quadratic daily-rankings self-join. Its cost lives inside
 *     one statement and is invisible below roughly a thousand dominions.
 *   - Finding 5.2, long transactions locking out live players. Commit cost and
 *     lock contention are not measured at all when everything is a savepoint.
 *
 * This command exists to close that gap. Point it at a database seeded with
 * dev:seed:realms and it reports the same shape of numbers as the benchmark
 * suite, at whatever N you actually have.
 */
class TickProfileCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'dev:tick:profile
                             {--round= : Round id to profile. Defaults to the most recent active round.}
                             {--commit : Keep the tick. By default everything is rolled back.}
                             {--rankings : Also profile updateDailyRankings(), which is where finding 4.1 lives.}
                             {--limit=15 : How many repeated statements to list.}
                             {--sample : Attribute PHP time to functions with the excimer sampling profiler. Requires the benchmark image.}
                             {--period=0.001 : Sampling period in seconds for --sample.}
                             {--collapsed= : Write folded stacks to this path for flamegraph rendering.}';

    /** @var string The console command description. */
    protected $description = 'Profiles a real tick at production scale and reports query counts and timings.';

    public function __construct(
        private readonly QueryProfilerService $profiler,
        private readonly TickService $tickService,
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('dev:tick:profile must never be run against production.');
        }

        $round = $this->resolveRound();
        $dominionCount = $round->activeDominions()->count();

        if ($dominionCount === 0) {
            throw new RuntimeException("Round {$round->id} has no active dominions. Seed it with dev:seed:realms first.");
        }

        $commit = (bool)$this->option('commit');

        $this->line('');
        $this->info(sprintf('Profiling round %d (%s)', $round->id, $round->name));
        $this->line(sprintf('  active dominions   %s', number_format($dominionCount)));
        $this->line(sprintf('  mode               %s', $commit ? 'COMMIT - the tick will be kept' : 'rollback - nothing is kept, mail discarded'));
        $this->line('');

        if ($commit && !$this->confirmCommit($round, $dominionCount)) {
            $this->warn('Aborted.');
            return;
        }

        if (!$commit) {
            // A database rollback cannot recall an email. The hourly digest
            // notification is ShouldQueue and the default queue connection is
            // sync, so it would be delivered inline part-way through a
            // profiling run. Discard mail for the duration instead.
            config(['mail.default' => 'array']);
        }

        if ($commit) {
            $this->warmUp($round);
            $this->profileTick($round, $dominionCount);
        } else {
            // Warm-up runs inside the transaction too: it calls
            // precalculateTick(), which saves, so leaving it outside meant the
            // command persisted one dominion's prediction while reporting that
            // nothing was kept.
            $this->withRollback(function () use ($round, $dominionCount): void {
                $this->warmUp($round);
                $this->profileTick($round, $dominionCount);
            });
        }

        if ($this->option('rankings')) {
            $this->profileRankings($round, $commit);
        }
    }

    private function resolveRound(): Round
    {
        $roundId = $this->option('round');

        if ($roundId !== null) {
            $round = Round::find((int)$roundId);

            if ($round === null) {
                throw new RuntimeException("No round with id {$roundId}.");
            }

            return $round;
        }

        $round = Round::active()->orderByDesc('start_date')->first();

        if ($round === null) {
            throw new RuntimeException('No active round found. Pass --round= explicitly.');
        }

        return $round;
    }

    /**
     * Committing a tick advances real game state and cannot be undone, so it
     * takes an explicit confirmation naming what is about to change.
     */
    private function confirmCommit(Round $round, int $dominionCount): bool
    {
        $this->warn(sprintf(
            'This will TICK %s dominions in round %d for real. Resources, queues, spells and',
            number_format($dominionCount),
            $round->id
        ));
        $this->warn('history will all advance by an hour, and it cannot be undone.');
        $this->line('');

        return $this->confirm('Continue?', false);
    }

    /**
     * Runs the profile inside a transaction that is always rolled back.
     *
     * This is the default because pointing a profiler at a dev database should
     * not silently consume a game hour. The tradeoff is the same one the PHPUnit
     * benchmarks make: the tick's inner transactions become savepoints, so commit
     * cost is not measured. Use --commit when that is what you are after.
     */
    private function withRollback(callable $callback): void
    {
        DB::beginTransaction();

        try {
            $callback();
        } finally {
            DB::rollBack();
            $this->line('  (rolled back)');
            $this->line('');
        }
    }

    /**
     * Excludes one-off cache population from the measurement, matching what
     * AbstractTickBenchmarkTestCase does, so the numbers stay comparable.
     */
    private function warmUp(Round $round): void
    {
        $this->profiler->withoutRecording(function () use ($round): void {
            $dominion = $round->activeDominions()->with('race')->first();

            if ($dominion !== null) {
                $this->tickService->precalculateTick($dominion);
            }
        });
    }

    private function profileTick(Round $round, int $dominionCount): void
    {
        $sampler = $this->startSampler();

        $this->profiler->start();
        $startedAt = hrtime(true);

        try {
            $this->tickService->performTick($round);
        } catch (Throwable $e) {
            $this->profiler->stop();
            throw $e;
        }

        $wallMs = (hrtime(true) - $startedAt) / 1000000;
        $queries = $this->profiler->stop();

        $this->report('performTick()', $queries, $wallMs, $dominionCount);
        $this->reportSamples($sampler);
    }

    /**
     * Starts the excimer sampling profiler, if --sample was passed and the
     * extension is present.
     *
     * Query counts tell you nothing about the ~75% of the tick that is spent in
     * PHP rather than in the driver. This is what attributes that share to
     * actual functions.
     *
     * @return \ExcimerProfiler|null
     */
    private function startSampler(): ?object
    {
        if (!$this->option('sample')) {
            return null;
        }

        if (!extension_loaded('excimer')) {
            $this->warn('--sample needs the excimer extension; run this in the benchmark image:');
            $this->warn('  docker compose run --rm benchmark php artisan dev:tick:profile --sample');
            $this->line('');

            return null;
        }

        $profiler = new \ExcimerProfiler();
        $profiler->setPeriod((float)$this->option('period'));
        $profiler->setEventType(EXCIMER_REAL);
        $profiler->start();

        return $profiler;
    }

    /**
     * @param \ExcimerProfiler|null $sampler
     */
    private function reportSamples(?object $sampler): void
    {
        if ($sampler === null) {
            return;
        }

        $sampler->stop();
        $log = $sampler->getLog();

        $collapsedPath = $this->option('collapsed');

        if ($collapsedPath) {
            $directory = dirname($collapsedPath);

            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }

            file_put_contents($collapsedPath, $log->formatCollapsed());
            $this->line(sprintf('  folded stacks written to %s', $collapsedPath));
        }

        $aggregated = $log->aggregateByFunction();

        if ($aggregated === []) {
            $this->warn('  excimer collected no samples - the run may have been too short for the sampling period.');

            return;
        }

        $totalSamples = array_sum(array_column($aggregated, 'self'));

        $this->line('');
        $this->info(sprintf('PHP time by function (%s samples at %ss)', number_format($totalSamples), $this->option('period')));
        $this->line(str_repeat('-', 72));

        // Self time is what identifies the function actually burning CPU;
        // inclusive time identifies the caller responsible for it. Both are
        // needed - a hot leaf is useless without knowing who calls it.
        uasort($aggregated, static fn (array $a, array $b): int => $b['self'] <=> $a['self']);

        $rows = [];

        foreach (array_slice($aggregated, 0, (int)$this->option('limit'), true) as $function => $counts) {
            $rows[] = [
                number_format($counts['self']),
                sprintf('%.1f%%', $totalSamples > 0 ? ($counts['self'] / $totalSamples) * 100 : 0),
                number_format($counts['inclusive']),
                mb_strimwidth($function, 0, 78, '...'),
            ];
        }

        $this->table(['self', 'self %', 'incl', 'function'], $rows);
    }

    /**
     * Profiles the ranking pass for the round being profiled.
     *
     * Calls updateDailyRankingsForRound() rather than the scheduler's
     * updateDailyRankings(), which sweeps every eligible round and skips any
     * whose start hour is not the current hour - so it profiled a no-op for 23
     * hours out of 24, and never the round named by --round.
     */
    private function profileRankings(Round $round, bool $commit): void
    {
        $run = function () use ($round): array {
            $this->profiler->start();
            $startedAt = hrtime(true);

            try {
                $this->tickService->updateDailyRankingsForRound($round);
            } catch (Throwable $e) {
                $this->profiler->stop();
                throw $e;
            }

            return [$this->profiler->stop(), (hrtime(true) - $startedAt) / 1000000];
        };

        if ($commit) {
            [$queries, $wallMs] = $run();
        } else {
            DB::beginTransaction();

            try {
                [$queries, $wallMs] = $run();
            } finally {
                DB::rollBack();
            }
        }

        $this->report('updateDailyRankings()', $queries, $wallMs, null);

        $this->line('  Finding 4.1 lives in the daily_rankings self-join above. If one statement');
        $this->line('  dominates the timing, that is the unindexed range self-join.');
        $this->line('');
    }

    /**
     * @param array<int, array{sql: string, bindings: array, time: float}> $queries
     */
    private function report(string $label, array $queries, float $wallMs, ?int $dominionCount): void
    {
        $queryMs = array_sum(array_column($queries, 'time'));
        $count = count($queries);

        $this->line('');
        $this->info($label);
        $this->line(str_repeat('-', 72));

        $rows = [
            ['queries', number_format($count)],
            ['wall time', sprintf('%s ms', number_format($wallMs, 1))],
            ['in database', sprintf('%s ms (%s%%)', number_format($queryMs, 1), $this->percentage($queryMs, $wallMs))],
            ['in php', sprintf('%s ms (%s%%)', number_format(max(0, $wallMs - $queryMs), 1), $this->percentage($wallMs - $queryMs, $wallMs))],
            ['peak memory', sprintf('%s MB', number_format(memory_get_peak_usage(true) / 1048576, 1))],
        ];

        if ($dominionCount !== null && $dominionCount > 0) {
            $rows[] = ['queries per dominion', number_format($count / $dominionCount, 2)];
            $rows[] = ['wall time per dominion', sprintf('%s ms', number_format($wallMs / $dominionCount, 2))];
        }

        $this->table(['metric', 'value'], $rows);

        $this->renderByTable($queries, $dominionCount);
        $this->renderRepeats($queries, $dominionCount);
    }

    /**
     * @param array<int, array{sql: string, bindings: array, time: float}> $queries
     */
    private function renderByTable(array $queries, ?int $dominionCount): void
    {
        $counts = [];

        foreach ($queries as $query) {
            $table = QueryProfilerService::primaryTable($query['sql']) ?? '(unknown)';
            $counts[$table] = ($counts[$table] ?? 0) + 1;
        }

        arsort($counts);

        $rows = [];

        foreach (array_slice($counts, 0, 15, true) as $table => $count) {
            $rows[] = [
                $table,
                number_format($count),
                $dominionCount ? number_format($count / $dominionCount, 2) : '-',
            ];
        }

        $this->table(['table', 'queries', 'per dominion'], $rows);
    }

    /**
     * @param array<int, array{sql: string, bindings: array, time: float}> $queries
     */
    private function renderRepeats(array $queries, ?int $dominionCount): void
    {
        $groups = [];

        foreach ($queries as $query) {
            $normalized = QueryProfilerService::normalize($query['sql']);

            if (!isset($groups[$normalized])) {
                $groups[$normalized] = ['count' => 0, 'totalMs' => 0.0];
            }

            $groups[$normalized]['count']++;
            $groups[$normalized]['totalMs'] += $query['time'];
        }

        uasort($groups, static fn (array $a, array $b): int => $b['totalMs'] <=> $a['totalMs']);

        $rows = [];

        foreach (array_slice($groups, 0, (int)$this->option('limit'), true) as $sql => $stats) {
            $rows[] = [
                number_format($stats['totalMs'], 1),
                number_format($stats['count']),
                $dominionCount ? number_format($stats['count'] / $dominionCount, 2) : '-',
                mb_strimwidth($sql, 0, 90, '...'),
            ];
        }

        $this->line('  Slowest statements by total time - repeats per dominion above ~1 are N+1s:');
        $this->table(['total ms', 'runs', 'per dom', 'statement'], $rows);
    }

    private function percentage(float $part, float $whole): string
    {
        if ($whole <= 0) {
            return '0';
        }

        return number_format(($part / $whole) * 100, 0);
    }
}
