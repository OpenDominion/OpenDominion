<?php

namespace OpenDominion\Services\Activity;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use OpenDominion\Models\Round;
use OpenDominion\Models\UserOrigin;
use OpenDominion\Models\UserOriginLookup;

class OriginLookupReportService
{
    public const STATUS_LOOKED_UP = 'looked_up';
    public const STATUS_FAILED = 'failed';
    public const STATUS_NOT_ATTEMPTED = 'not_attempted';

    /**
     * Fraud score at or above which IPQS considers an IP high risk.
     */
    public const HIGH_RISK_SCORE = 85;

    /**
     * Fraud score at or above which IPQS considers an IP suspicious.
     */
    public const SUSPICIOUS_SCORE = 75;

    /**
     * Reports on every looked-up IP address used by a round's player dominions.
     *
     * @param Round $round
     * @return array{summary: array<string, mixed>, results: array<int, array<string, mixed>>}
     */
    public function getRoundReport(Round $round): array
    {
        $ipAddresses = UserOriginLookup::query()
            ->whereIn('ip_address', $this->getRoundOriginsQuery($round)->select('ip_address'))
            ->whereNotNull('data')
            ->pluck('ip_address');

        $results = $this->buildResults($round, $ipAddresses->mapWithKeys(fn (string $ipAddress) => [$ipAddress => self::STATUS_LOOKED_UP])->all());

        return [
            'summary' => $this->summarize($results),
            'results' => $results,
        ];
    }

    /**
     * Reports on a single lookup run.
     *
     * @param Round $round
     * @param string $tier
     * @param Collection<int, array{ip_address: string, user_id: int, tier: string}> $selection
     * @param array{results: array<string, bool>, stopped_early: bool} $batch
     * @param float $durationSeconds
     * @param int $remaining
     * @return array<string, mixed>
     */
    public function getRunReport(Round $round, string $tier, Collection $selection, array $batch, float $durationSeconds, int $remaining): array
    {
        $statusesByIpAddress = [];
        foreach ($selection as $selectedIp) {
            $ipAddress = $selectedIp['ip_address'];
            if (!array_key_exists($ipAddress, $batch['results'])) {
                $statusesByIpAddress[$ipAddress] = self::STATUS_NOT_ATTEMPTED;
            } else {
                $statusesByIpAddress[$ipAddress] = $batch['results'][$ipAddress] ? self::STATUS_LOOKED_UP : self::STATUS_FAILED;
            }
        }

        $results = $this->buildResults($round, $statusesByIpAddress);
        $statusCounts = array_count_values($statusesByIpAddress);

        return [
            'round_id' => $round->id,
            'round_name' => $round->name,
            'tier' => $tier,
            'finished_at' => now()->toDateTimeString(),
            'duration_seconds' => (int) round($durationSeconds),
            'selected' => $selection->count(),
            'looked_up' => $statusCounts[self::STATUS_LOOKED_UP] ?? 0,
            'failed' => $statusCounts[self::STATUS_FAILED] ?? 0,
            'not_attempted' => $statusCounts[self::STATUS_NOT_ATTEMPTED] ?? 0,
            'remaining' => $remaining,
            'stopped_early' => $batch['stopped_early'],
            'summary' => $this->summarize($results),
            'results' => $results,
        ];
    }

    /**
     * Builds one report row per IP address, most severe first.
     *
     * @param Round $round
     * @param array<string, string> $statusesByIpAddress
     * @return array<int, array<string, mixed>>
     */
    protected function buildResults(Round $round, array $statusesByIpAddress): array
    {
        $ipAddresses = array_keys($statusesByIpAddress);

        $lookups = UserOriginLookup::query()
            ->whereIn('ip_address', $ipAddresses)
            ->get()
            ->keyBy('ip_address');

        $originsByIpAddress = $this->getRoundOriginsQuery($round)
            ->with(['dominion.realm', 'user'])
            ->whereIn('ip_address', $ipAddresses)
            ->get()
            ->groupBy('ip_address');

        $results = [];
        foreach ($statusesByIpAddress as $ipAddress => $status) {
            $origins = $originsByIpAddress->get($ipAddress, collect())->sortByDesc('count');
            $lookup = $status === self::STATUS_LOOKED_UP ? $lookups->get($ipAddress) : null;

            $results[] = [
                'ip_address' => $ipAddress,
                'status' => $status,
                'users' => $origins->unique('user_id')
                    ->map(fn (UserOrigin $origin) => ['id' => $origin->user_id, 'display_name' => $origin->user->display_name])
                    ->values()
                    ->all(),
                'dominions' => $origins
                    ->map(fn (UserOrigin $origin) => "{$origin->dominion->name} (#{$origin->dominion->realm->number})")
                    ->values()
                    ->all(),
                'organization' => $lookup?->organization,
                'isp' => $lookup?->isp,
                'country' => $lookup?->country,
                'has_anonymizer_data' => $lookup !== null && $lookup->hasAnonymizerData(),
                'flags' => $lookup ? $lookup->getAnonymizerFlags() : [],
                'score' => $lookup?->score,
            ];
        }

        usort($results, fn (array $a, array $b) => $this->getResultSortKey($a) <=> $this->getResultSortKey($b));

        return $results;
    }

    /**
     * Counts flags, clean IPs and risky fraud scores across looked-up report rows.
     *
     * @param array<int, array<string, mixed>> $results
     * @return array{looked_up: int, flag_counts: array<int, array{label: string, severity: string, count: int}>, clean: int, high_risk: int, suspicious: int}
     */
    protected function summarize(array $results): array
    {
        $lookedUpResults = array_filter($results, fn (array $result) => $result['status'] === self::STATUS_LOOKED_UP);

        $flagCounts = [];
        foreach (UserOriginLookup::ANONYMIZER_FLAGS as $flag) {
            $count = count(array_filter($lookedUpResults, fn (array $result) => in_array($flag['label'], array_column($result['flags'], 'label'), true)));
            if ($count > 0) {
                $flagCounts[] = $flag + ['count' => $count];
            }
        }

        return [
            'looked_up' => count($lookedUpResults),
            'flag_counts' => $flagCounts,
            'clean' => count(array_filter($lookedUpResults, fn (array $result) => $result['has_anonymizer_data'] && empty($result['flags']))),
            'high_risk' => count(array_filter($lookedUpResults, fn (array $result) => $result['score'] !== null && $result['score'] >= self::HIGH_RISK_SCORE)),
            'suspicious' => count(array_filter($lookedUpResults, fn (array $result) => $result['score'] !== null && $result['score'] >= self::SUSPICIOUS_SCORE && $result['score'] < self::HIGH_RISK_SCORE)),
        ];
    }

    /**
     * Orders report rows: looked-up IPs by most severe flag then score, then failures, then skipped IPs.
     *
     * @param array<string, mixed> $result
     * @return array<int, int|float|string>
     */
    protected function getResultSortKey(array $result): array
    {
        $statusRank = [self::STATUS_LOOKED_UP => 0, self::STATUS_FAILED => 1, self::STATUS_NOT_ATTEMPTED => 2][$result['status']];
        $severityRank = ['danger' => 0, 'warning' => 1, 'secondary' => 2][$result['flags'][0]['severity'] ?? ''] ?? 3;

        return [$statusRank, $severityRank, -($result['score'] ?? -1), $result['ip_address']];
    }

    /**
     * Returns a query for the origins recorded by the player dominions of a round.
     *
     * @param Round $round
     * @return Builder
     */
    protected function getRoundOriginsQuery(Round $round): Builder
    {
        return UserOrigin::query()
            ->whereIn('dominion_id', $round->dominions()->whereNotNull('dominions.user_id')->select('dominions.id'));
    }
}
