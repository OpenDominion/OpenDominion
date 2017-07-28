<?php

namespace OpenDominion\Contracts\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface RezoningCalculator
{
    /**
     * Returns the Dominion's rezoning platinum cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion);

    /**
     * Returns the maximum number of acres of land a Dominion can rezone.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion);
}
