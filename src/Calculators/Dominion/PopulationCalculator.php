<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class PopulationCalculator
{
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

    public function getPopulationBirthRaw(Dominion $dominion)
    {
        return 0; // todo
    }

    public function getPopulationBirthModifier(Dominion $dominion)
    {
        return 1; // todo
    }

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getPopulationPeasantGrowth(Dominion $dominion)
    {
        return (int)max(
            ((-0.05 * $dominion->peasants) - $this->getPopulationDrafteeGrowth($dominion)),
            min(
                ($this->getMaxPopulation($dominion) - $dominion->peasants - $dominion->getPopulationMilitary($dominion) - $this->getPopulationDrafteeGrowth($dominion)),
                ($this->getPopulationBirth($dominion) - $this->getPopulationDrafteeGrowth($dominion))
            )
        );
    }

    /**
     * Returns the Dominion's population draftee growth.
     *
     * @param Dominion $dominion
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth(Dominion $dominion)
    {
        $draftees = 0;

        // Values (percentages)
        $growth_factor = 1;

        if ($this->getPopulationMilitaryPercentage($dominion) < $dominion->draft_rate) {
            $draftees += ($dominion->peasants * ($growth_factor / 100));
        }

        return (int)$draftees;
    }

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @param Dominion $dominion
     *
     * @return float
     */
    public function getPopulationPeasantPercentage(Dominion $dominion)
    {
        return (($dominion->peasants / $dominion->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @param Dominion $dominion
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion)
    {
        return (($dominion->getPopulationMilitary() / $dominion->getPopulation()) * 100);
    }

    public function getPopulationMilitaryMaxTrainable(Dominion $dominion)
    {
        return 0; // todo
    }
}
