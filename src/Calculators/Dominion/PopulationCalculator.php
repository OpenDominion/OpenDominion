<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class PopulationCalculator
{
    /**
     * Returns the Dominion's population, military and non-military.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getPopulation(Dominion $dominion)
    {
        return ($dominion->peasants + $this->getPopulationMilitary($dominion));
    }

    /**
     * Returns the Dominion's military population.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getPopulationMilitary(Dominion $dominion)
    {
        return (
            $dominion->draftees
            + $dominion->military_unit1
            + $dominion->military_unit2
            + $dominion->military_unit3
            + $dominion->military_unit4
            + $dominion->military_spies
            + $dominion->military_wizards
            + $dominion->military_archmages
        );
    }

    /**
     * Returns the Dominion's max population.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getMaxPopulation(Dominion $dominion)
    {
        $population = 0;

        // Raw pop * multiplier
        $population += ($this->getRawMaxPopulation($dominion) * $this->getPopulationMaxMultiplier($dominion));

        // todo
        // Military
//        $population += min(
//            ($this->getPopulationMilitary($dominion) - $dominion->draftees),
//            ($dominion->building_barracks * 36),
//        );

        return $population;
    }

    public function getRawMaxPopulation(Dominion $dominion)
    {
        return 0; // todo
    }

    /**
     * Returns the Dominion's population max multiplier.
     *
     * Population max multiplier is affected by:
     * - Racial bonuses
     * - Prestige bonus
     *
     * @param Dominion $dominion
     *
     * @return float
     */
    public function getPopulationMaxMultiplier(Dominion $dominion)
    {
        $multiplier = 1.0;

        // Racial bonus
        // todo

        // Racial bonus
        $multiplier *= (1 + ($dominion->prestige / 10000));

        return $multiplier;
    }

    public function getPopulationBirth(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationBirthModifier(Dominion $dominion)
    {
        return 1; // todo
    }

    public function getPopulationPeasantGrowth(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationDrafteeGrowth(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationPeasantPercentage(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationMilitaryPercentage(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationMilitaryMaxTrainable(Dominion $dominion)
    {
        return 0; // todo
    }
}
