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
    public function guardLockedDominion(Dominion $dominion): void
    {
        if ($dominion->isLocked()) {
            throw new GameException("Dominion {$dominion->name} is locked");
        }

        // Reassign active doms from Graveyard
        if ($dominion->user_id !== null && $dominion->realm->number == 0 && $dominion->round->isActive()) {
            $realmFinderService = app(\OpenDominion\Services\RealmFinderService::class);
            $newRealm = $realmFinderService->findRealm($dominion->round, $dominion->race, $dominion->user);
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
        if ($dominion->protection_ticks_remaining == 0) {
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
