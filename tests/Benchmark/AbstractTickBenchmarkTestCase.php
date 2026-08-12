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
use OpenDominion\Tests\Benchmark\Support\TickProfiler;

/**
 * Shared harness for the benchmark suites.
 *
 * Concrete subclasses must carry their own #[Group('benchmark')] attribute -
 * PHPUnit reads attributes off the test class itself and does not inherit them.
 */
abstract class AbstractTickBenchmarkTestCase extends AbstractTestCase
{
    protected TickProfiler $profiler;

    protected BenchmarkRoundSeeder $seeder;

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

    protected function tickService(): TickService
    {
        return $this->app->make(TickService::class);
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
    protected function warmUp(BenchmarkRound ...$fixtures): void
    {
        $tickService = $this->tickService();

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
     * STDERR rather than echo: PHPUnit captures test output and, depending on
     * configuration, flags it as risky. STDERR goes straight to the terminal.
     */
    protected function write(string $text): void
    {
        fwrite(STDERR, $text);
    }
}
