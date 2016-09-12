<?php

namespace OpenDominion\Calculators\Networth;

use OpenDominion\Models\Dominion;

class DominionNetworthCalculator
{
    /** @var UnitNetworthCalculator */
    protected $unitNetworthCalculator;

    /**
     * DominionNetworthCalculator constructor.
     *
     * @param UnitNetworthCalculator $unitNetworthCalculator
     */
    public function __construct(UnitNetworthCalculator $unitNetworthCalculator)
    {
        $this->unitNetworthCalculator = $unitNetworthCalculator;
    }

    /**
     * Calculates and returns a Dominion's networth.
     *
     * @param Dominion $dominion
     *
     * @return float
     */
    public function calculate(Dominion $dominion)
    {
        $networth = 0;

        foreach ($dominion->race->units as $unit) {
            $networth += ($this->unitNetworthCalculator->calculate($unit) * $dominion->{'military_unit' . $unit->slot});
        }

        $networth += (5 * $dominion->military_spies);
        $networth += (5 * $dominion->military_wizards);
        $networth += (5 * $dominion->military_archmages);

        // todo: land
        // todo: buildings

        return (float)$networth;
    }
}
