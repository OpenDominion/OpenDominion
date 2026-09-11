<?php

namespace OpenDominion\Services\Activity;

use Illuminate\Support\Sleep;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

class OriginLookupBatchService
{
    /**
     * Maximum number of IPs looked up in one run.
     */
    public const BATCH_SIZE = 15;

    /**
     * Seconds to wait between lookups in a run, to avoid IPQS throttling.
     */
    public const SPACING_SECONDS = 1;

    /**
     * Consecutive failed lookups after which a run stops early.
     */
    public const MAX_CONSECUTIVE_FAILURES = 2;

    public function __construct(
        protected ActivityService $activityService,
        protected OriginLookupSelectionService $originLookupSelectionService,
        protected OriginLookupReportService $originLookupReportService
    ) {
    }

    /**
     * Looks up the next batch of IPs in one tier of a round and reports the outcome.
     *
     * @param Round $round
     * @param string $tier
     * @return array<string, mixed> Run report; 'selected' is 0 when the tier had nothing left
     */
    public function run(Round $round, string $tier): array
    {
        $startedAt = now();

        $selection = $this->originLookupSelectionService->selectForRound($round, self::BATCH_SIZE, [$tier]);
        $batch = $this->performLookups($selection);

        return $this->originLookupReportService->getRunReport(
            $round,
            $tier,
            $selection,
            $batch,
            $startedAt->diffInSeconds(now()),
            $this->originLookupSelectionService->countAvailableByTier($round)[$tier]
        );
    }

    /**
     * Performs lookups for a selection of IP addresses, one at a time.
     *
     * Waits between lookups and stops early after too many consecutive
     * failures, so an IPQS outage or exhausted quota ends the run quickly.
     *
     * @param iterable<array{ip_address: string, user_id: int}> $selection
     * @return array{results: array<string, bool>, stopped_early: bool} Success keyed by IP address, for attempted IPs only
     */
    public function performLookups(iterable $selection): array
    {
        $results = [];
        $consecutiveFailures = 0;

        foreach ($selection as $selectedIp) {
            if ($consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
                return ['results' => $results, 'stopped_early' => true];
            }

            if (!empty($results)) {
                Sleep::for(self::SPACING_SECONDS)->seconds();
            }

            $user = User::find($selectedIp['user_id']);
            $success = $user !== null && $this->activityService->performLookup($user, $selectedIp['ip_address']);
            $results[$selectedIp['ip_address']] = $success;

            $consecutiveFailures = $success ? 0 : $consecutiveFailures + 1;
        }

        return ['results' => $results, 'stopped_early' => false];
    }
}
