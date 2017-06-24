<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface BuildingCalculator
{
    /**
     * Returns the Dominion's total number of constructed buildings.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalBuildings(Dominion $dominion);

    // todo: buildings under construction?
}
