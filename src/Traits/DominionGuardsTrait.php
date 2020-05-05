<?php

namespace OpenDominion\Traits;

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
}
