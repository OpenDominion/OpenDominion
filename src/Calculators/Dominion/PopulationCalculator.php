<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Traits\DominionAwareTrait;

class PopulationCalculator
{
    use DominionAwareTrait;

    /**
     * Returns the Dominion's max population.
     *
     * @return int
     */
    public function getMaxPopulation()
    {
        $population = 0;

        // Raw pop * multiplier
        $population += ($this->getRawMaxPopulation() * $this->getPopulationMaxMultiplier());

        // todo
        // Military
//        $population += min(
//            ($this->getPopulationMilitary($dominion) - $dominion->draftees),
//            ($dominion->building_barracks * 36),
//        );

        return $population;
    }

    public function getRawMaxPopulation()
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
    public function getPopulationMaxMultiplier()
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
        return (int)($this->getPopulationBirthRaw() * $this->getPopulationBirthModifier());
    }

    public function getPopulationBirthRaw()
    {
        return 0; // todo
    }

    public function getPopulationBirthModifier()
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
        return (($this->dominion->peasants / $this->dominion->getPopulation()) * 100);
    }

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage()
    {
        return (($this->dominion->getPopulationMilitary() / $this->dominion->getPopulation()) * 100);
    }

    public function getPopulationMilitaryMaxTrainable()
    {
        return 0; // todo
    }
}
