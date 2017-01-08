<?php

namespace OpenDominion\Calculators;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

class NetworthCalculator
{
    /**
     * Calculates and returns a Realm's networth.
     *
     * @param Realm $realm
     *
     * @return float
     */
    public function getRealmNetworth(Realm $realm)
    {
        // todo
    }

    /**
     * Calculates and returns a Dominion's networth.
     *
     * @param Dominion $dominion
     *
     * @return float
     */
    public function getDominionNetworth(Dominion $dominion)
    {
        $networth = 0;

        foreach ($dominion->race->units as $unit) {
            $networth += ($this->getUnitNetworth($unit) * $dominion->{'military_unit' . $unit->slot});
        }

        $networth += (5 * $dominion->military_spies);
        $networth += (5 * $dominion->military_wizards);
        $networth += (5 * $dominion->military_archmages);

        // todo: land
        // todo: buildings

        return (float)$networth;
    }

    /**
     * Calculates and returns a Unit's networth.
     *
     * @param Unit $unit
     *
     * @return float
     */
    public function getUnitNetworth(Unit $unit)
    {
        if (in_array($unit->slot, [1, 2])) {
            return 5;
        }

        return (float)(
            (1.8 * min(6, max($unit->power_offense, $unit->power_defense)))
            + (0.45 * min(6, min($unit->power_offense, $unit->power_defense)))
            + (0.2 * (max(($unit->power_offense - 6), 0) + max(($unit->power_defense - 6), 0)))
        );
    }
}
