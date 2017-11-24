<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

class NetworthCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * NetworthCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(BuildingCalculator $buildingCalculator, LandCalculator $landCalculator)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns a Realm's networth.
     *
     * @param Realm $realm
     * @return int
     */
    public function getRealmNetworth(Realm $realm): int
    {
        $networth = 0;

        // todo: fix line below which generates this query:
        // select * from "dominions" where "dominions"."realm_id" = '1' and "dominions"."realm_id" is not null
        foreach ($realm->dominions as $dominion) {
            $networth += $this->getDominionNetworth($dominion);
        }

        return $networth;
    }

    /**
     * Returns a Dominion's networth.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getDominionNetworth(Dominion $dominion): int
    {
        $networth = 0;

        // Values
        $networthPerSpy = 5;
        $networthPerWizard = 5;
        $networthPerArchMage = 5;
        $networthPerLand = 20;
        $networthPerBuilding = 5;

        foreach ($dominion->race->units as $unit) {
            $networth += ($dominion->{'military_unit' . $unit->slot} * $this->getUnitNetworth($unit));
        }

        $networth += ($dominion->military_spies * $networthPerSpy);
        $networth += ($dominion->military_wizards * $networthPerWizard);
        $networth += ($dominion->military_archmages * $networthPerArchMage);

        $networth += ($this->landCalculator->getTotalLand($dominion) * $networthPerLand);
        $networth += ($this->buildingCalculator->getTotalBuildings($dominion) * $networthPerBuilding);

        // todo: Certain units have conditional bonus DP/OP. Do we need to calculate those too?
        // racial networth bonuses (wood elf, dryad, sylvan, rockapult, gnome, adept, dark elf, frost mage, ice elemental, icekin)

        return round($networth);
    }

    /**
     * Returns a single Unit's networth.
     *
     * @param Unit $unit
     * @return float
     */
    public function getUnitNetworth(Unit $unit): float
    {
        if (in_array($unit->slot, [1, 2], false)) {
            return 5;
        }

        return (
            (1.8 * min(6, max($unit->power_offense, $unit->power_defense)))
            + (0.45 * min(6, min($unit->power_offense, $unit->power_defense)))
            + (0.2 * (max(($unit->power_offense - 6), 0) + max(($unit->power_defense - 6), 0)))
        );
    }
}
