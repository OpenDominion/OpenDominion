<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

class NetworthCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var bool */
    protected $forTick = false;

    /**
     * NetworthCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     * @param MilitaryCalculator $militaryCalculator
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator
    )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
    }

    /**
     * Toggle if this calculator should include the following hour's resources.
     */
    public function setForTick(bool $value)
    {
        $this->forTick = $value;
        $this->militaryCalculator->setForTick($value);
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
     * @param bool $fresh
     * @return int
     */
    public function getDominionNetworth(Dominion $dominion, bool $fresh = false): int
    {
        $networth = $dominion->calculated_networth;

        if(!$fresh && $networth > 0) {
            return $networth;
        }

        $networth = 0;

        // Values
        $networthPerSpy = 5;
        $networthPerAssassin = 5;
        $networthPerWizard = 5;
        $networthPerArchMage = 5;
        $networthPerLand = 20;
        $networthPerBuilding = 5;

        foreach ($dominion->race->units as $unit) {
            $totalUnitsOfType = $this->militaryCalculator->getTotalUnitsForSlot($dominion, $unit->slot);
            $networth += $totalUnitsOfType * $this->getUnitNetworth($dominion, $unit);
        }

        $networth += ($dominion->military_spies * $networthPerSpy);
        $networth += ($dominion->military_assassins * $networthPerAssassin);
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
     * @param Dominion $dominion
     * @param Unit $unit
     * @return float
     */
    public function getUnitNetworth(Dominion $dominion, Unit $unit): float
    {
        $networthPerSpecialist = 5;
        $networthPerElitePoint = 2;

        if (in_array($unit->slot, [1, 2], false)) {
            return $networthPerSpecialist;
        }

        $unitOffense = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, 1, $unit, 'offense');
        $unitDefense = $this->militaryCalculator->getUnitPowerWithPerks($dominion, null, 1, $unit, 'defense');

        // Special case for kobold pairing
        if ($dominion->race->key == 'kobold') {
            $unitOffense += 2;
            $unitDefense += 2;
        }
        $highestStat = max($unitOffense, $unitDefense);

        return round($networthPerElitePoint * $highestStat, 2);
    }
}
