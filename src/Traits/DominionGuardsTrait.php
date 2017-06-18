<?php

namespace OpenDominion\Traits;

use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Models\Dominion;

trait DominionGuardsTrait
{
    /**
     * Guards against locked Dominions.
     *
     * @param Dominion $dominion
     * @throws DominionLockedException
     */
    public function guardLockedDominion(Dominion $dominion)
    {
        if ($dominion->isLocked()) {
            throw new DominionLockedException;
        }
    }
}
