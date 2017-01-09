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

        // Raw pop * multiplier
        $population += ($this->getMaxPopulationRaw() * $this->getMaxPopulationMultiplier());

        // todo
        // Military
//        $population += min(
//            ($this->getPopulationMilitary($dominion) - $dominion->draftees),
//            ($dominion->building_barracks * 36),
//        );

        return $population;
    }

    public function getMaxPopulationRaw()
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
     * @return float
     */
    public function getMaxPopulationMultiplier()
    {
        $multiplier = 1.0;

        // Racial bonus
        // todo

        // Racial bonus
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
                ($this->getMaxPopulation() - $this->dominion->peasants - $this->dominion->getPopulationMilitary() - $this->getPopulationDrafteeGrowth()),
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
