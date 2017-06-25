<?php

namespace OpenDominion\Contracts\Calculators\Dominion;

use OpenDominion\Models\Dominion;

interface MilitaryCalculator
{
    public function getOffensivePower(Dominion $dominion);

    public function getOffensivePowerRaw(Dominion $dominion);

    public function getOffensivePowerMultiplier(Dominion $dominion);

    public function getOffensivePowerRatio(Dominion $dominion);

    public function getOffensivePowerRatioRaw(Dominion $dominion);

    public function getDefensivePower(Dominion $dominion);

    public function getDefensivePowerRaw(Dominion $dominion);

    public function getDefensivePowerMultiplier(Dominion $dominion);

    public function getDefensivePowerRatio(Dominion $dominion);

    public function getDefensivePowerRatioRaw(Dominion $dominion);

    public function getSpyRatio(Dominion $dominion);

    public function getSpyRatioRaw(Dominion $dominion);

    public function getSpyStrengthRegen(Dominion $dominion);

    public function getWizardRatio(Dominion $dominion);

    public function getWizardRatioRaw(Dominion $dominion);

    public function getWizardStrengthRegen(Dominion $dominion);
}
