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
    }

    /**
     * Guards against actions during tick.
     *
     * @param int $seconds
     * @throws RuntimeException
     */
    public function guardActionsDuringTick(int $seconds = 3): void
    {
        $requestTimestamp = request()->server('REQUEST_TIME');
        if ($requestTimestamp !== null) {
            $requestTime = Carbon::createFromTimestamp($requestTimestamp);
            if ($requestTime->minute == 0 && $requestTime->second < $seconds) {
                throw new GameException('The Emperor is currently collecting taxes and cannot fulfill your request. Please try again.');
            }
        }
    }
}
