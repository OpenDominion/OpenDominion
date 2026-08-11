<?php

namespace OpenDominion\Tests\Benchmark;

use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\TickService;
use OpenDominion\Tests\AbstractTestCase;
use OpenDominion\Tests\Benchmark\Support\BenchmarkRound;
use OpenDominion\Tests\Benchmark\Support\BenchmarkRoundSeeder;
use OpenDominion\Tests\Benchmark\Support\TickProfile;
use OpenDominion\Tests\Benchmark\Support\TickProfiler;
use PHPUnit\Framework\Attributes\Group;

/**
 * Phase 0 of the tick benchmarking work: measure, do not judge.
 *
 * This class has no budgets. Its job is to turn the call-graph arithmetic in
 * firstroundfindings.txt and secondroundfindings.txt into numbers from a real
 * database, so that phase 1 can freeze those numbers as hard budgets.
 *
 * Run with:
 *   php artisan test --group=benchmark
 *   BENCH_REPORT=1 php artisan test --group=benchmark   (also writes JSON)
 *
 * Caveats that apply to every number below:
 *
 *  - AbstractTestCase uses DatabaseTransactions, so the tick's own
 *    DB::transaction calls degrade to savepoints. Commit cost and lock
 *    contention are NOT measured; finding 5.2 needs a real-scale profile run.
 *  - Wall time is machine-dependent and should never be asserted on. Query
 *    counts are what phase 1 will ratchet.
 *  - N here is 10 and 40. Production is in the thousands. Linear behaviour is
 *    established by the slope between the two, not by absolute magnitude.
 */
#[Group('benchmark')]
class TickBaselineReportTest extends AbstractTestCase
{
    private const SMALL_N = 10;

    private const LARGE_N = 40;

    private TickProfiler $profiler;

    private BenchmarkRoundSeeder $seeder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->profiler = new TickProfiler();
        $this->seeder = new BenchmarkRoundSeeder(
            $this->app->make(DominionFactory::class),
            $this->app->make(RealmFactory::class),
            $this->app->make(TickService::class),
        );
    }

    /**
     * Isolates the two single-dominion entry points.
     *
     * precalculateTick is the ~20-queries-per-dominion claim in finding 2.1;
     * measuring it alone separates it from cleanup and notifications, which the
     * production Log::info at TickService.php:436 lumps together.
     */
    public function testReportSingleDominionBaseline(): void
    {
        $fixture = $this->seeder->seed(1);
        $tickService = $this->app->make(TickService::class);
        $dominion = Dominion::findOrFail($fixture->dominionIds[0]);

        $this->warmUp($fixture);


        $precalculate = $this->profiler->profile(
            'precalculateTick() - one dominion',
            static function () use ($tickService, $dominion): void {
                $tickService->precalculateTick($dominion);
            },
            1
        );

        $singleTick = $this->profiler->profile(
            'performTick($round, $dominion) - single dominion path',
            static function () use ($tickService, $fixture, $dominion): void {
                $tickService->performTick($fixture->round, $dominion);
            },
            1
        );

        $this->report($fixture, [$precalculate, $singleTick]);

        $this->assertGreaterThan(0, $precalculate->queryCount());
    }

    /**
     * The scaling measurement. Two independent rounds are seeded so the same
     * process ticks both; the marginal cost between them is the per-dominion
     * slope that phase 1 will budget against.
     */
    public function testReportRoundBaselineAcrossDominionCounts(): void
    {
        $small = $this->seeder->seed(self::SMALL_N);
        $large = $this->seeder->seed(self::LARGE_N);

        $tickService = $this->app->make(TickService::class);

        $this->warmUp($small, $large);

        $smallProfile = $this->profiler->profile(
            sprintf('performTick($round) - whole round, N = %d', self::SMALL_N),
            static function () use ($tickService, $small): void {
                $tickService->performTick($small->round);
            },
            self::SMALL_N
        );

        $largeProfile = $this->profiler->profile(
            sprintf('performTick($round) - whole round, N = %d', self::LARGE_N),
            static function () use ($tickService, $large): void {
                $tickService->performTick($large->round);
            },
            self::LARGE_N
        );

        $this->report($small, [$smallProfile]);
        $this->report($large, [$largeProfile]);
        $this->reportSlope($smallProfile, $largeProfile);

        $this->assertGreaterThan(0, $largeProfile->queryCount());
    }

    /**
     * Excludes one-off setup from the measurement: container singletons,
     * autoloading, opcache, and the two rememberForever caches the tick reads
     * through - Race::findWithRelationsCached and Round::findCached.
     *
     * EVERY fixture that will be measured has to be warmed, not just the first.
     * The race cache is keyed per race id, so a larger round touches races a
     * smaller one never did; leaving those cold would charge the larger run for
     * cache population and fake a superlinear slope.
     *
     * What this deliberately hides: roughly one query per distinct race plus one
     * per round. That is fixed reference-data cost, not per-dominion cost, and
     * whether production pays it hourly depends on its cache driver.
     *
     * precalculateTick is deterministic, so re-running it on an already-seeded
     * dominion leaves the fixture unchanged.
     */
    private function warmUp(BenchmarkRound ...$fixtures): void
    {
        $tickService = $this->app->make(TickService::class);

        foreach ($fixtures as $fixture) {
            $raceIds = Dominion::whereIn('id', $fixture->dominionIds)
                ->distinct()
                ->pluck('race_id');

            $dominion = Dominion::findOrFail($fixture->dominionIds[0]);

            $this->profiler->warmUp(static function () use ($tickService, $fixture, $raceIds, $dominion): void {
                Round::findCached($fixture->round->id);

                foreach ($raceIds as $raceId) {
                    Race::findWithRelationsCached($raceId);
                }

                $tickService->precalculateTick($dominion);
            });
        }
    }

    /**
     * @param array<int, TickProfile> $profiles
     */
    private function report(BenchmarkRound $fixture, array $profiles): void
    {
        $this->write(PHP_EOL . 'fixture: ' . $fixture->describe() . PHP_EOL);

        foreach ($profiles as $profile) {
            $this->write($profile->render());
            $this->writeJson($profile);
        }
    }

    /**
     * The number phase 1 cares about: how many queries each additional dominion
     * costs, isolated from the round's fixed overhead.
     */
    private function reportSlope(TickProfile $small, TickProfile $large): void
    {
        $deltaQueries = $large->queryCount() - $small->queryCount();
        $deltaDominions = self::LARGE_N - self::SMALL_N;
        $marginal = $deltaQueries / $deltaDominions;
        $fixedOverhead = $small->queryCount() - ($marginal * self::SMALL_N);

        $lines = [
            str_repeat('=', 78),
            'SCALING',
            str_repeat('=', 78),
            sprintf('  N = %-4d %6d queries   (%.2f per dominion)', self::SMALL_N, $small->queryCount(), $small->queriesPerDominion()),
            sprintf('  N = %-4d %6d queries   (%.2f per dominion)', self::LARGE_N, $large->queryCount(), $large->queriesPerDominion()),
            '',
            sprintf('  marginal cost    %.2f queries per additional dominion', $marginal),
            sprintf('  fixed overhead   %.1f queries per round', $fixedOverhead),
            '',
            sprintf('  wall time        %.1f ms -> %.1f ms (%.2fx for %.2fx the dominions)',
                $small->wallTimeMs(),
                $large->wallTimeMs(),
                $small->wallTimeMs() > 0 ? $large->wallTimeMs() / $small->wallTimeMs() : 0,
                self::LARGE_N / self::SMALL_N
            ),
            '',
            '  A marginal cost close to the per-dominion figure means linear scaling.',
            '  A marginal cost materially above it means something is superlinear.',
            '',
        ];

        $this->write(implode(PHP_EOL, $lines) . PHP_EOL);
    }

    /**
     * STDERR rather than echo: PHPUnit captures test output and, depending on
     * configuration, flags it as risky. STDERR goes straight to the terminal.
     */
    private function write(string $text): void
    {
        fwrite(STDERR, $text);
    }

    private function writeJson(TickProfile $profile): void
    {
        if (getenv('BENCH_REPORT') === false || getenv('BENCH_REPORT') === '') {
            return;
        }

        $directory = storage_path('benchmarks');

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filename = sprintf(
            '%s/tick-%s.json',
            $directory,
            preg_replace('/[^a-z0-9]+/i', '-', strtolower($profile->label()))
        );

        file_put_contents($filename, json_encode($profile->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
