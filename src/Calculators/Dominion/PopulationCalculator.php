<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Traits\DominionAwareTrait;

class PopulationCalculator
{
    use DominionAwareTrait;

    /**
     * Returns the Dominion's population, military and non-military.
     *
     * @return int
     */
    public function getPopulation()
    {
        return ($this->dominion->peasants + $this->getPopulationMilitary());
    }

    /**
     * Returns the Dominion's military population.
     *
     * @return int
     */
    public function getPopulationMilitary()
    {
        return (
            $this->dominion->draftees
            + $this->dominion->military_unit1
            + $this->dominion->military_unit2
            + $this->dominion->military_unit3
            + $this->dominion->military_unit4
            + $this->dominion->military_spies
            + $this->dominion->military_wizards
            + $this->dominion->military_archmages
        );
    }

    /**
     * Returns the Dominion's max population.
     *
     * @return int
     */
    public function getMaxPopulation()
    {
        $population = 0;

        // Values
        $troopsPerBarracks = 36;

        // Raw pop * multiplier
        $population += ($this->getMaxPopulationRaw() * $this->getMaxPopulationMultiplier());

        // Military
        $population += min(
            ($this->getPopulationMilitary() - $this->dominion->draftees),
            ($this->dominion->building_barracks * $troopsPerBarracks)
        );

        return round($population);
    }

    /**
     * Returns the Dominion's raw max population.
     *
     * Raw max population is calculated by buildings (except barracks) and barren land.
     *
     * @return int
     */
    public function getMaxPopulationRaw()
    {
        $population = 0;

        // Values
        $housingPerHome = 30;
        $housingPerNonHome = 15; // except barracks
        $housingPerBarracks = 0;
        //$housingPerBarrenLand = 5;

        // todo: race bonus for barren land

        $buildingTypes = []; // todo: BuildingHelper::getBuildingTypeS()

        foreach (array_keys($buildingTypes) as $buildingType) {
            $housing = 0;

            switch ($buildingType) {
                case 'home':
                    $housing = $housingPerHome;
                    break;

                case 'barracks':
                    $housing = $housingPerBarracks;
                    break;

                default:
                    $housing = $housingPerNonHome;
                    break;
            }

            $population += ($this->dominion->{'building_' . $buildingType} * $housing);
        }

        // Housing per barren land
        //$population += ($this->landCalculator->getTotalBarrenLand() * $housingPerBarrenLand);

        return $population;
    }

    /**
     * Returns the Dominion's max population multiplier.
     *
     * Max population multiplier is affected by:
     * - Racial bonuses (todo)
     * - Improvement: Keep (todo)
     * - Tech: Urban Mastery and Construction (todo)
     * - Prestige bonus
     *
     * @return float
     */
    public function getMaxPopulationMultiplier()
    {
        $multiplier = 1.0;

        // Values
//        $techUrbanMasteryMultiplier = 1.075;
//        $techConstructionMultiplier = 1.02;

        // Racial bonus
        // todo

        // Improvement: Keep
        // todo

        // Tech: Urban Mastery
        // todo

        // Tech: Construction
        // todo

        // Prestige bonus
        $multiplier *= (1 + ($this->dominion->prestige / 10000));

        return $multiplier;
    }

    /**
     * Returns the Dominion's population birth.
     *
     * @return int
     */
    public function getPopulationBirth()
    {
        return (int)($this->getPopulationBirthRaw() * $this->getPopulationBirthMultiplier());
    }

    public function getPopulationBirthRaw()
    {
        return 0; // todo
    }

    public function getPopulationBirthMultiplier()
    {
        return 1; // todo
    }

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @return int
     */
    public function getPopulationPeasantGrowth()
    {
        return (int)max(
            ((-0.05 * $this->dominion->peasants) - $this->getPopulationDrafteeGrowth()),
            min(
                ($this->getMaxPopulation() - $this->dominion->peasants - $this->getPopulationMilitary() - $this->getPopulationDrafteeGrowth()),
                ($this->getPopulationBirth() - $this->getPopulationDrafteeGrowth())
            )
        );
    }

    /**
     * Returns the Dominion's population draftee growth.
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth()
    {
        $draftees = 0;

        // Values (percentages)
        $growth_factor = 1;

        if ($this->getPopulationMilitaryPercentage() < $this->dominion->draft_rate) {
            $draftees += ($this->dominion->peasants * ($growth_factor / 100));
        }

        return (int)$draftees;
    }

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @return float
     */
    public function getPopulationPeasantPercentage()
    {
        return (($this->dominion->peasants / $this->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage()
    {
        return (($this->getPopulationMilitary() / $this->getPopulation()) * 100);
    }

    public function getPopulationMilitaryMaxTrainable()
    {
        return 0; // todo
    }
}
