<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Events\QueryExecuted;

/**
 * Records the queries issued by a callback.
 *
 * Lives in src/ rather than tests/ because both the benchmark suite and the
 * dev:tick:profile command depend on it, and their numbers are only comparable
 * if they normalize and attribute SQL identically. tests/ is autoload-dev, so a
 * console command cannot reach into it.
 *
 * A single listener is attached lazily and gated by a recording flag: Laravel
 * offers no way to detach a DB::listen callback, so registering one per
 * measurement would double-count every subsequent run.
 *
 * @phpstan-type QueryRecord array{sql: string, bindings: array, time: float}
 */
class QueryProfilerService
{
    private bool $listening = false;

    private bool $recording = false;

    /** @var array<int, array{sql: string, bindings: array, time: float}> */
    private array $queries = [];

    /**
     * Begins recording, discarding anything captured previously.
     */
    public function start(): void
    {
        $this->attachListener();

        $this->queries = [];
        $this->recording = true;
    }

    /**
     * Stops recording and returns what was captured.
     *
     * @return array<int, array{sql: string, bindings: array, time: float}>
     */
    public function stop(): array
    {
        $this->recording = false;

        return $this->queries;
    }

    /**
     * Runs a callback with recording suspended. Used to exclude warm-up work
     * from a measurement.
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function withoutRecording(callable $callback)
    {
        $wasRecording = $this->recording;
        $this->recording = false;

        try {
            return $callback();
        } finally {
            $this->recording = $wasRecording;
        }
    }

    /**
     * Collapses bind-list width and whitespace so the same logical statement
     * groups together regardless of how many ids it was called with.
     */
    public static function normalize(string $sql): string
    {
        $sql = preg_replace('/\s+/', ' ', trim($sql));
        $sql = preg_replace('/\(\s*\?(?:\s*,\s*\?)*\s*\)/', '(?)', $sql);

        return $sql;
    }

    /**
     * The first table named by a statement, which is the one people reason about
     * when attributing cost.
     */
    public static function primaryTable(string $sql): ?string
    {
        if (preg_match('/\b(?:from|into|update|join)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $matches) === 1) {
            return $matches[1];
        }

        return null;
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
