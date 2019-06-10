<?php

namespace OpenDominion\Traits;

use OpenDominion\Models\Dominion;
use RuntimeException;

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
            throw new RuntimeException("Dominion {$dominion->name} is locked");
        }
    }
}
