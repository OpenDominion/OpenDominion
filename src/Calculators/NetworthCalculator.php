<?php

namespace OpenDominion\Calculators;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Interfaces\DependencyInitializableInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Unit;

class NetworthCalculator implements DependencyInitializableInterface
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * {@inheritDoc}
     */
    public function initDependencies()
    {
        $this->buildingCalculator = app()->make(BuildingCalculator::class);
        $this->landCalculator = app()->make(LandCalculator::class);
    }

    /**
     * Calculates and returns a Realm's networth.
     *
     * @param Realm $realm
     *
     * @return int
     */
    public function getRealmNetworth(Realm $realm)
    {
        $networth = 0;

        foreach ($realm->dominions as $dominion) {
            $networth += $this->getDominionNetworth($dominion);
        }

        return $networth;
    }

    /**
     * Calculates and returns a Dominion's networth.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getDominionNetworth(Dominion $dominion)
    {
        $this->buildingCalculator->setDominion($dominion);
        $this->landCalculator->setDominion($dominion);

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

        $networth += ($this->landCalculator->getTotalLand() * $networthPerLand);
        $networth += ($this->buildingCalculator->getTotalBuildings() * $networthPerBuilding);

        // Todo: racial network bonuses (wood elf, dryad, sylvan, rockapult, gnome, adept, dark elf, frost mage, ice elemental, icekin)

        return (int)round($networth);
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
