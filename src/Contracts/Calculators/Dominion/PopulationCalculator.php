<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface PopulationCalculator
{
    /**
     * Returns the Dominion's total population, both peasants and military.
     *
     * @return int
     */
    public function getPopulation(Dominion $dominion);

    /**
     * Returns the Dominion's military population.
     *
     * The military consists of draftees, combat units, spies, wizards and archmages.
     *
     * @return int
     */
    public function getPopulationMilitary(Dominion $dominion);

    /**
     * Returns the Dominion's max population.
     *
     * @return int
     */
    public function getMaxPopulation(Dominion $dominion);

    /**
     * Returns the Dominion's raw max population.
     *
     * Maximum population is determined by housing in homes, other buildings (sans barracks) and barren land.
     *
     * @return float
     */
    public function getMaxPopulationRaw(Dominion $dominion);

    /**
     * Returns the Dominion's max population multiplier.
     *
     * Max population multiplier is affected by:
     * - Racial Bonus
     * - Improvement: Keep (todo)
     * - Tech: Urban Mastery and Construction (todo)
     * - Prestige bonus
     *
     * @return float
     */
    public function getMaxPopulationMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's max population military bonus.
     *
     * @return float
     */
    public function getMaxPopulationMilitaryBonus(Dominion $dominion);

    /**
     * Returns the Dominion's population birth.
     *
     * @return int
     */
    public function getPopulationBirth(Dominion $dominion);

    /**
     * Returns the Dominions raw population birth.
     *
     * @return float
     */
    public function getPopulationBirthRaw(Dominion $dominion);

    /**
     * Returns the Dominion's population birth multiplier.
     *
     * @return float
     */
    public function getPopulationBirthMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's population peasant growth.
     *
     * @return int
     */
    public function getPopulationPeasantGrowth(Dominion $dominion);

    /**
     * Returns the Dominion's population draftee growth.
     *
     * Draftee growth is influenced by draft rate.
     *
     * @return int
     */
    public function getPopulationDrafteeGrowth(Dominion $dominion);

    /**
     * Returns the Dominion's population peasant percentage.
     *
     * @return float
     */
    public function getPopulationPeasantPercentage(Dominion $dominion);

    /**
     * Returns the Dominion's population military percentage.
     *
     * @return float
     */
    public function getPopulationMilitaryPercentage(Dominion $dominion);

    /**
     * Returns the Dominion's employment jobs.
     *
     * Each building (sans home and barracks) employs 20 peasants.
     *
     * @return int
     */
    public function getEmploymentJobs(Dominion $dominion);

    /**
     * Returns the Dominion's employed population.
     *
     * The employed population consists of the Dominion's peasant count, up to the number of max available jobs.
     *
     * @return int
     */
    public function getPopulationEmployed(Dominion $dominion);

    /**
     * Returns the Dominion's employment percentage.
     *
     * If employment is at or above 100%, then one should strive to build more homes to get more peasants to the working
     * force. If employment is below 100%, then one should construct more buildings to employ idle peasants.
     *
     * @return float
     */
    public function getEmploymentPercentage(Dominion $dominion);
}
