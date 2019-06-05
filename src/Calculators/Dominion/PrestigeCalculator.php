<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class PrestigeCalculator
{
    /**
     * Returns the Dominion's prestige multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPrestigeMultiplier(Dominion $dominion): float
    {
        return ($dominion->prestige / 10000);
    }
}
