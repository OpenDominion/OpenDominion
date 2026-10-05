<?php

namespace OpenDominion\Services;

use OpenDominion\Models\Round;
use OpenDominion\Models\RoundSetupRun;

class RoundSetupService
{
    public const REALM_ASSIGNMENT = 'realm_assignment';
    public const NON_PLAYER_GENERATION = 'non_player_generation';

    /**
     * Run a setup phase once, committing its effects and completion receipt together.
     * The callback receives the current round after its exclusive lock is acquired.
     */
    public function run(Round $round, string $operation, callable $callback): bool
    {
        return $round->getConnection()->transaction(function () use ($round, $operation, $callback): bool {
            $lockedRound = Round::query()->whereKey($round->id)->lockForUpdate()->firstOrFail();
            if (RoundSetupRun::query()->where('round_id', $lockedRound->id)->where('operation', $operation)->exists()) {
                return false;
            }

            $callback($lockedRound);

            RoundSetupRun::query()->create([
                'round_id' => $lockedRound->id,
                'operation' => $operation,
                'completed_at' => now(),
            ]);

            return true;
        });
    }
}
