<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface LandCalculator
{
    /**
     * Returns the Dominion's total acres of land.
     *
     * @return int
     */
    public function getTotalLand(Dominion $dominion);

    /**
     * Returns the Dominion's total acres of barren land.
     *
     * @return int
     */
    public function getTotalBarrenLand(Dominion $dominion);

    /**
     * Returns the Dominion's total barren land by land type.
     *
     * @param string $landType
     * @return int
     */
    public function getTotalBarrenLandByLandType(Dominion $dominion, $landType);

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @return int[]
     */
    public function getBarrenLand(Dominion $dominion);
}
