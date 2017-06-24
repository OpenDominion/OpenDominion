<?php

namespace OpenDominion\Contracts\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface ExplorationCalculator
{
    /**
     * Returns the Dominion's exploration platinum cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion);

    /**
     * Returns the Dominion's exploration draftee cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getDrafteeCost(Dominion $dominion);

    /**
     * Returns the maximum number of acres of land a Dominion can afford to
     * explore.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion);

    /**
     * Returns the morale drop after exploring for $amount of acres of land.
     *
     * @param int $amount
     * @return int
     * @todo Does this really belong here? Maybe it should go in a helper, since it isn't dependent on a Dominion instance
     */
    public function getMoraleDrop($amount);
}
