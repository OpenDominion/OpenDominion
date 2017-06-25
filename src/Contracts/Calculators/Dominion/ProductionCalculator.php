<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface ProductionCalculator
{
    /**
     * Returns the Dominion's platinum production.
     *
     * @return int
     */
    public function getPlatinumProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw platinum production.
     *
     * @return float
     */
    public function getPlatinumProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's platinum production multiplier.
     *
     * @return float
     */
    public function getPlatinumProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's food production.
     *
     * @return int
     */
    public function getFoodProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw food production.
     *
     * @return float
     */
    public function getFoodProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's food production multiplier.
     *
     * @return float
     */
    public function getFoodProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's food consumption.
     *
     * @return float
     */
    public function getFoodConsumption(Dominion $dominion);

    /**
     * Returns the Dominion's food decay.
     *
     * @return float
     */
    public function getFoodDecay(Dominion $dominion);

    /**
     * Returns the Dominion's net food change.
     *
     * @return int
     */
    public function getFoodNetChange(Dominion $dominion);

    /**
     * Returns the Dominion's lumber production.
     *
     * @return int
     */
    public function getLumberProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw lumber production.
     *
     * @return float
     */
    public function getLumberProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's lumber production multiplier.
     *
     * @return float
     */
    public function getLumberProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's lumber decay.
     *
     * @return float
     */
    public function getLumberDecay(Dominion $dominion);

    /**
     * Returns the Dominion's net lumber change.
     *
     * @return int
     */
    public function getLumberNetChange(Dominion $dominion);

    /**
     * Returns the Dominion's mana production.
     *
     * @return int
     */
    public function getManaProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw mana production.
     *
     * @return float
     */
    public function getManaProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's mana production multiplier.
     *
     * @return float
     */
    public function getManaProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's mana decay.
     *
     * @return float
     */
    public function getManaDecay(Dominion $dominion);

    /**
     * Returns the Dominion's net mana change.
     *
     * @return int
     */
    public function getManaNetChange(Dominion $dominion);

    /**
     * Returns the Dominion's ore production.
     *
     * @return int
     */
    public function getOreProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw ore production.
     *
     * @return float
     */
    public function getOreProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's ore production multiplier.
     *
     * @return float
     */
    public function getOreProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's gem production.
     *
     * @return int
     */
    public function getGemProduction(Dominion $dominion);

    /**
     * Returns the Dominion's raw gem production.
     *
     * @return float
     */
    public function getGemProductionRaw(Dominion $dominion);

    /**
     * Returns the Dominion's gem production multiplier.
     *
     * @return float
     */
    public function getGemProductionMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's boat production.
     *
     * @return int
     */
    public function getBoatProduction(Dominion $dominion);
}
