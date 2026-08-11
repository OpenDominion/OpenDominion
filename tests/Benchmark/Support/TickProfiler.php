<?php

namespace OpenDominion\Tests\Benchmark\Support;

use DB;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Records query counts, timings and memory for a callback.
 *
 * A single listener is attached lazily and gated by a recording flag, because
 * Laravel offers no way to detach a DB::listen callback - registering one per
 * measurement would double-count every subsequent run.
 */
final class TickProfiler
{
    private bool $listening = false;

    private bool $recording = false;

    /** @var array<int, array{sql: string, bindings: array, time: float}> */
    private array $queries = [];

    /**
     * Runs a callback without recording it.
     *
     * Always warm up before measuring. The container binds every calculator as a
     * singleton, Race::findWithRelationsCached and Round::findCached populate the
     * array cache on first touch, and PHP resolves autoloads lazily - none of
     * which are per-tick costs in production, where the process is long-lived.
     */
    public function warmUp(callable $callback): void
    {
        $wasRecording = $this->recording;
        $this->recording = false;

        try {
            $callback();
        } finally {
            $this->recording = $wasRecording;
        }
    }

    public function profile(string $label, callable $callback, ?int $dominionCount = null): TickProfile
    {
        $this->attachListener();

        $this->queries = [];

        gc_collect_cycles();

        $memoryBefore = memory_get_usage(true);
        $startedAt = hrtime(true);

        $this->recording = true;

        try {
            $callback();
        } finally {
            $wallTimeMs = (hrtime(true) - $startedAt) / 1000000;
            $this->recording = false;
        }

        return new TickProfile(
            $label,
            $this->queries,
            $wallTimeMs,
            memory_get_usage(true) - $memoryBefore,
            memory_get_peak_usage(true),
            $dominionCount
        );
    }

    private function attachListener(): void
    {
        if ($this->listening) {
            return;
        }

        DB::listen(function (QueryExecuted $query): void {
            if (!$this->recording) {
                return;
            }

            $this->queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => (float)$query->time,
            ];
        });

        $this->listening = true;
    }
}
