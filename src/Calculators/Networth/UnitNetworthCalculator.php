<?php

namespace OpenDominion\Calculators\Networth;

use OpenDominion\Models\Unit;

class UnitNetworthCalculator
{
    /**
     * Calculates and returns a Unit's networth.
     *
     * @param Unit $unit
     *
     * @return float
     */
    public function calculate(Unit $unit)
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
