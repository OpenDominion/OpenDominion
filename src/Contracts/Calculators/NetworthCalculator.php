<?php

namespace OpenDominion\Contracts\Calculators;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

interface NetworthCalculator
{
    /**
     * Calculates and returns a Realm's networth.
     *
     * @param Realm $realm
     *
     * @return int
     */
    public function getRealmNetworth(Realm $realm);

    /**
     * Calculates and returns a Dominion's networth.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getDominionNetworth(Dominion $dominion);

    /**
     * Calculates and returns a Unit's networth.
     *
     * @param Unit $unit
     *
     * @return float
     */
    public function getUnitNetworth(Unit $unit);
}
