<?php

namespace OpenDominion\Calculators\Dominion;

class MilitaryCalculator extends AbstractDominionCalculator
{
    /**
     * {@inheritDoc}
     */
    public function initDependencies()
    {
        // todo
    }

    public function getOffensivePower(){}
    public function getOffensivePowerRaw(){}
    public function getOffensivePowerMultiplier(){}

    public function getDefensivePower(){}
    public function getDefensivePowerRaw(){}
    public function getDefensivePowerMultiplier(){}
    // todo: split net and raw DP into draftees and non-draftees?

    public function getSpyRatio(){} // SPA
    public function getWizardRatio(){} // WPA
}
