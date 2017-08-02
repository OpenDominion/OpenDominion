<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface MilitaryCalculator
{
    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePower(Dominion $dominion);

    /**
     * Returns the Dominion's raw offensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRaw(Dominion $dominion);

    /**
     * Returns the Dominion's offensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatio(Dominion $dominion);

    /**
     * Returns the Dominion's raw offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatioRaw(Dominion $dominion);

    /**
     * Returns the Dominion's defensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePower(Dominion $dominion);

    /**
     * Returns the Dominion's raw defensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRaw(Dominion $dominion);

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion);

    /**
     * Returns the Dominion's defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatio(Dominion $dominion);

    /**
     * Returns the Dominion's raw defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatioRaw(Dominion $dominion);

    /**
     * Returns the Dominion's spy ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatio(Dominion $dominion);

    /**
     * Returns the Dominion's raw spy ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion);

    /**
     * Returns the Dominion's spy strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyStrengthRegen(Dominion $dominion);

    /**
     * Returns the Dominion's wizard ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatio(Dominion $dominion);

    /**
     * Returns the Dominion's raw wizard ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion);

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion);
}
