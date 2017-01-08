<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Calculators\Networth\UnitNetworthCalculator;
use OpenDominion\Models\Dominion;

class NetworthCalculator
{
    /** @var Dominion */
    protected $dominion;

    /** @var UnitNetworthCalculator */
    protected $unitNetworthCalculator;

    /**
     * DominionNetworthCalculator constructor.
     *
     * @param Dominion $dominion
     * @param UnitNetworthCalculator $unitNetworthCalculator
     */
    public function __construct(Dominion $dominion, UnitNetworthCalculator $unitNetworthCalculator)
    {
        $this->dominion = $dominion;
        $this->unitNetworthCalculator = $unitNetworthCalculator;
    }

    /**
     * Calculates and returns a Dominion's networth.
     *
     * @return float
     */
    public function getNetworth()
    {
        $networth = 0;

        foreach ($this->dominion->race->units as $unit) {
            $networth += ($this->unitNetworthCalculator->calculate($unit) * $this->dominion->{'military_unit' . $unit->slot});
        }

        $networth += (5 * $this->dominion->military_spies);
        $networth += (5 * $this->dominion->military_wizards);
        $networth += (5 * $this->dominion->military_archmages);

        // todo: land
        // todo: buildings

        return (float)$networth;
    }

    /**
     * @param Dominion $dominion
     */
    public function setDominion($dominion)
    {
        $this->dominion = $dominion;
    }
}
