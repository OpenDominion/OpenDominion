<?php

namespace OpenDominion\Interfaces\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface LandCalculatorInterface
{
    /**
     * {@inheritDoc}
     */
    public function initDependencies();

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion);

    /**
     * Returns the Dominion's total acres of land.
     *
     * @return int
     */
    public function getTotalLand();

    /**
     * Returns the Dominion's total acres of barren land.
     *
     * @return int
     */
    public function getTotalBarrenLand();

    /**
     * Returns the Dominion's total barren land by land type.
     *
     * @param string $landType
     * @return int
     */
    public function getTotalBarrenLandByLandType($landType);

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @return int[]
     */
    public function getBarrenLandByLandType();

    /**
     * Returns the Dominion's exploration platinum cost per acre.
     *
     * @return int
     */
    public function getExplorationPlatinumCost();

    /**
     * Returns the Dominion's exploration draftee cost per acre.
     *
     * @return int
     */
    public function getExplorationDrafteeCost();

    /**
     * Returns the maximum number of acres a Dominion can afford.
     *
     * @return int
     */
    public function getExplorationMaxAfford();

    /**
     * Returns the Dominion's morale drop after exploring for $amount of acres.
     *
     * @param $amount
     * @return int
     */
    public function getExplorationMoraleDrop($amount);

    /**
     * Returns the Dominion's rezoning cost per acre.
     *
     * @param \OpenDominion\Models\Dominion $dominion
     * @return int
     */
    public function getRezoningPlatinumCost(Dominion $dominion);
}
