<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Tech;

class TechCalculator
{
    /**
     * Returns the Dominion's current research point cost to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechCost(Dominion $dominion): int
    {
        return 10000;
    }

    /**
     * Determine if the Dominion meets the requirements to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasPrerequisites(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return $tech->prerequisites == null || count(array_intersect($tech->prerequisites, $unlockedTechs)) != 0;
    }
}
