<?php

namespace OpenDominion\Tests\Benchmark\Support;

use OpenDominion\Services\QueryProfilerService;

/**
 * Immutable result of a single profiled run.
 *
 * Query count is the primary metric: it is deterministic across machines and
 * maps directly onto the findings in firstroundfindings.txt / secondroundfindings.txt.
 * Timings are recorded for reporting only - never assert on them.
 *
 * @phpstan-type QueryRecord array{sql: string, bindings: array, time: float}
 */
final class TickProfile
{
    /**
     * @param array<int, array{sql: string, bindings: array, time: float}> $queries
     */
    public function __construct(
        private readonly string $label,
        private readonly array $queries,
        private readonly float $wallTimeMs,
        private readonly int $memoryDeltaBytes,
        private readonly int $peakMemoryBytes,
        private readonly ?int $dominionCount = null,
    ) {
    }

    public function label(): string
    {
        return $this->label;
    }

    public function dominionCount(): ?int
    {
        return $this->dominionCount;
    }

    public function queryCount(): int
    {
        return count($this->queries);
    }

    public function wallTimeMs(): float
    {
        return $this->wallTimeMs;
    }

    /**
     * Sum of the database's own reported execution time. Always less than
     * wallTimeMs(); the difference is PHP-side compute.
     */
    public function queryTimeMs(): float
    {
        return array_sum(array_column($this->queries, 'time'));
    }

    /**
     * Time spent outside the database driver - the calculator and perk-rebuild
     * cost that findings 2.3 / 2.4 / M2 are about.
     */
    public function computeTimeMs(): float
    {
        return max(0.0, $this->wallTimeMs - $this->queryTimeMs());
    }

    public function memoryDeltaBytes(): int
    {
        return $this->memoryDeltaBytes;
    }

    /**
     * Process-wide peak, not a delta - memory_get_peak_usage() is monotonic, so
     * this is only comparable between runs in the same process and only ratchets
     * upwards.
     */
    public function peakMemoryBytes(): int
    {
        return $this->peakMemoryBytes;
    }

    public function queriesPerDominion(): ?float
    {
        if ($this->dominionCount === null || $this->dominionCount === 0) {
            return null;
        }

        return $this->queryCount() / $this->dominionCount;
    }

    /**
     * Query counts attributed to the primary (first-mentioned) table of each
     * statement, ordered by count descending.
     *
     * @return array<string, int>
     */
    public function queryCountsByTable(): array
    {
        $counts = [];

        foreach ($this->queries as $query) {
            $table = $this->primaryTable($query['sql']) ?? '(unknown)';
            $counts[$table] = ($counts[$table] ?? 0) + 1;
        }

        arsort($counts);

        return $counts;
    }

    /**
     * Number of statements whose SQL contains the given fragment, matched
     * case-insensitively. Use for targeted budgets, e.g. countMatching('dominion_spells').
     */
    public function countMatching(string $fragment): int
    {
        $matches = 0;

        foreach ($this->queries as $query) {
            if (stripos($query['sql'], $fragment) !== false) {
                $matches++;
            }
        }

        return $matches;
    }

    /**
     * Number of statements whose SQL matches the given regex. Used to isolate a
     * specific phase, e.g. the three bulk join-updates of phase A.
     */
    public function countMatchingPattern(string $pattern): int
    {
        $matches = 0;

        foreach ($this->queries as $query) {
            if (preg_match($pattern, $this->normalize($query['sql'])) === 1) {
                $matches++;
            }
        }

        return $matches;
    }

    /**
     * The normalized statements matching a regex, keyed by SQL and counted.
     * Exists so a failing budget can show what it actually saw.
     *
     * @return array<string, int>
     */
    public function statementsMatching(string $pattern): array
    {
        $found = [];

        foreach ($this->queries as $query) {
            $normalized = $this->normalize($query['sql']);

            if (preg_match($pattern, $normalized) === 1) {
                $found[$normalized] = ($found[$normalized] ?? 0) + 1;
            }
        }

        arsort($found);

        return $found;
    }

    /**
     * The single most-repeated statement, as [sql, count]. Null when nothing ran
     * more than once.
     *
     * @return array{0: string, 1: int}|null
     */
    public function mostRepeatedStatement(): ?array
    {
        $groups = $this->duplicateGroups();

        if ($groups === []) {
            return null;
        }

        $sql = array_key_first($groups);

        return [$sql, $groups[$sql]];
    }

    /**
     * Statements executed more than once, keyed by normalized SQL and ordered by
     * execution count descending. This is the N+1 detector: a statement repeated
     * once per dominion (or once per row) shows up at the top.
     *
     * @return array<string, int>
     */
    public function duplicateGroups(int $minimumCount = 2): array
    {
        $groups = [];

        foreach ($this->queries as $query) {
            $normalized = $this->normalize($query['sql']);
            $groups[$normalized] = ($groups[$normalized] ?? 0) + 1;
        }

        $groups = array_filter($groups, static fn (int $count): bool => $count >= $minimumCount);

        arsort($groups);

        return $groups;
    }

    /**
     * The slowest statements by total accumulated time, keyed by normalized SQL.
     *
     * @return array<string, array{count: int, totalMs: float}>
     */
    public function slowestGroups(int $limit = 10): array
    {
        $groups = [];

        foreach ($this->queries as $query) {
            $normalized = $this->normalize($query['sql']);

            if (!isset($groups[$normalized])) {
                $groups[$normalized] = ['count' => 0, 'totalMs' => 0.0];
            }

            $groups[$normalized]['count']++;
            $groups[$normalized]['totalMs'] += $query['time'];
        }

        uasort($groups, static fn (array $a, array $b): int => $b['totalMs'] <=> $a['totalMs']);

        return array_slice($groups, 0, $limit, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'label' => $this->label,
            'dominions' => $this->dominionCount,
            'queries' => $this->queryCount(),
            'queriesPerDominion' => $this->queriesPerDominion(),
            'wallTimeMs' => round($this->wallTimeMs, 2),
            'queryTimeMs' => round($this->queryTimeMs(), 2),
            'computeTimeMs' => round($this->computeTimeMs(), 2),
            'memoryDeltaMb' => round($this->memoryDeltaBytes / 1048576, 2),
            'peakMemoryMb' => round($this->peakMemoryBytes / 1048576, 2),
            'byTable' => $this->queryCountsByTable(),
            'duplicates' => $this->duplicateGroups(),
        ];
    }

    /**
     * Human-readable report block, written to STDERR by the baseline test.
     */
    public function render(int $duplicateLimit = 12): string
    {
        $lines = [];

        $lines[] = str_repeat('=', 78);
        $lines[] = sprintf('%s%s', $this->label, $this->dominionCount !== null ? "  (N = {$this->dominionCount})" : '');
        $lines[] = str_repeat('=', 78);

        $perDominion = $this->queriesPerDominion();

        $lines[] = sprintf('  queries          %d%s', $this->queryCount(), $perDominion !== null
            ? sprintf('   (%.2f per dominion)', $perDominion)
            : '');
        $lines[] = sprintf('  wall time        %.1f ms', $this->wallTimeMs);
        $lines[] = sprintf('  in database      %.1f ms (%.0f%%)', $this->queryTimeMs(), $this->percentageOfWall($this->queryTimeMs()));
        $lines[] = sprintf('  in php           %.1f ms (%.0f%%)', $this->computeTimeMs(), $this->percentageOfWall($this->computeTimeMs()));
        $lines[] = sprintf('  memory delta     %.1f MB (process peak %.1f MB)', $this->memoryDeltaBytes / 1048576, $this->peakMemoryBytes / 1048576);

        $lines[] = '';
        $lines[] = '  queries by table';

        foreach ($this->queryCountsByTable() as $table => $count) {
            $lines[] = sprintf('    %-32s %6d%s', $table, $count, $perDominion !== null
                ? sprintf('  (%.2f/dom)', $count / $this->dominionCount)
                : '');
        }

        $duplicates = $this->duplicateGroups();

        if ($duplicates !== []) {
            $lines[] = '';
            $lines[] = sprintf('  repeated statements (top %d) - N+1 candidates', $duplicateLimit);

            foreach (array_slice($duplicates, 0, $duplicateLimit, true) as $sql => $count) {
                $lines[] = sprintf('    %6dx  %s', $count, $this->truncate($sql, 96));
            }
        }

        $lines[] = '';
        $lines[] = '  slowest statements by total time';

        foreach ($this->slowestGroups(8) as $sql => $stats) {
            $lines[] = sprintf(
                '    %8.1f ms  %5dx  %s',
                $stats['totalMs'],
                $stats['count'],
                $this->truncate($sql, 84)
            );
        }

        $lines[] = '';

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function percentageOfWall(float $milliseconds): float
    {
        if ($this->wallTimeMs <= 0.0) {
            return 0.0;
        }

        return ($milliseconds / $this->wallTimeMs) * 100;
    }

    /**
     * Delegated to the shared service so test-scale and real-scale reports group
     * and attribute statements identically.
     */
    private function normalize(string $sql): string
    {
        return QueryProfilerService::normalize($sql);
    }

    private function primaryTable(string $sql): ?string
    {
        return QueryProfilerService::primaryTable($sql);
    }

    private function truncate(string $value, int $length): string
    {
        if (strlen($value) <= $length) {
            return $value;
        }

        return substr($value, 0, $length - 3) . '...';
    }
}
