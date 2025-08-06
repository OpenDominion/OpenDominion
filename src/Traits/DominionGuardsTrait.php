<?php

namespace OpenDominion\Traits;

use Carbon\Carbon;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;

trait DominionGuardsTrait
{
    /**
     * Guards against locked Dominions.
     *
     * @param Dominion $dominion
     * @throws RuntimeException
     */
    public function guardLockedDominion(Dominion $dominion, bool $ignoreBuildPhase = false): void
    {
        if ($dominion->isLocked()) {
            throw new GameException("Dominion {$dominion->name} is locked");
        }

        if ($dominion->isBuildingPhase() && !$ignoreBuildPhase) {
            throw new GameException('You have not confirmed your starting buildings');
        }

        // Reassign active doms from Graveyard
        if ($dominion->user_id !== null && $dominion->realm->number == 0 && $dominion->round->isActive()) {
            $realmAssignmentService = app(\OpenDominion\Services\RealmAssignmentService::class);
            $newRealm = $realmAssignmentService->findRealm($dominion->round, $dominion->race, $dominion->user);
            $dominion->update([
                'realm_id' => $newRealm->id,
                'monarchy_vote_for_dominion_id' => null
            ]);
        }
    }

    /**
     * Guards against actions during tick.
     *
     * @param Dominion $dominion
     * @param int $seconds
     * @throws RuntimeException
     */
    public function guardActionsDuringTick(Dominion $dominion, int $seconds = 3): void
    {
        if ($dominion->protection_finished) {
            $requestTimestamp = request()->server('REQUEST_TIME');
            if ($requestTimestamp !== null) {
                $requestTime = Carbon::createFromTimestamp($requestTimestamp);
                if ($requestTime->minute == 0 && $requestTime->second < $seconds) {
                    throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
                }
            }
        }
    }
}
