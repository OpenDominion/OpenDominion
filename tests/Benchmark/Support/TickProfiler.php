<?php

namespace OpenDominion\Tests\Benchmark\Support;

use OpenDominion\Services\QueryProfilerService;

/**
 * Adds timing, memory and TickProfile construction on top of the shared
 * QueryProfilerService.
 *
 * Query capture and SQL normalization deliberately live in the service rather
 * than here, so the benchmark suite and dev:tick:profile measure the same thing
 * the same way and their numbers can be compared directly.
 */
final class TickProfiler
{
    private QueryProfilerService $recorder;

    public function __construct(?QueryProfilerService $recorder = null)
    {
        $this->recorder = $recorder ?? new QueryProfilerService();
    }

    /**
     * Runs a callback without recording it.
     *
     * Always warm up before measuring. The container binds every calculator as a
     * singleton, Race::findWithRelationsCached and Round::findCached populate the
     * cache on first touch, and PHP resolves autoloads lazily - none of which are
     * per-tick costs in a long-lived production process.
     */
    public function warmUp(callable $callback): void
    {
        $this->recorder->withoutRecording($callback);
    }

    public function profile(string $label, callable $callback, ?int $dominionCount = null): TickProfile
    {
        gc_collect_cycles();

        $memoryBefore = memory_get_usage(true);
        $startedAt = hrtime(true);

        $this->recorder->start();

        try {
            $callback();
        } finally {
            $wallTimeMs = (hrtime(true) - $startedAt) / 1000000;
            $queries = $this->recorder->stop();
        }

        return new TickProfile(
            $label,
            $queries,
            $wallTimeMs,
            memory_get_usage(true) - $memoryBefore,
            memory_get_peak_usage(true),
            $dominionCount
        );
    }
}
