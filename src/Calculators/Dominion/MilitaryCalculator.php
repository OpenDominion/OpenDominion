<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class MilitaryCalculator extends AbstractDominionCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * {@inheritDoc}
     */
    public function initDependencies()
    {
        $this->landCalculator = app()->make(LandCalculator::class);
    }

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->landCalculator->setDominion($dominion);

        return $this;
    }

    public function getOffensivePower(){}
    public function getOffensivePowerRaw(){}
    public function getOffensivePowerMultiplier(){}
    public function getOffensivePowerRatio(){}
    public function getOffensivePowerRatioRaw(){}

    public function getDefensivePower(){}
    public function getDefensivePowerRaw(){}
    public function getDefensivePowerMultiplier(){}
    // todo: split net and raw DP into draftees and non-draftees?
    public function getDefensivePowerRatio(){}
    public function getDefensivePowerRatioRaw(){}

    public function getSpyRatio()
    {
        return $this->getSpyRatioRaw();
        // todo: racial spy strength multiplier
    }

    public function getSpyRatioRaw()
    {
        return (float)($this->dominion->military_spies / $this->landCalculator->getTotalLand());
    }

    public function getWizardRatio()
    {
        return $this->getWizardRatioRaw();
        // todo: racial multiplier + Magical Weaponry tech (+15%)
    }

    public function getWizardRatioRaw()
    {
        return (float)(($this->dominion->military_wizards + ($this->dominion->military_archmages * 2)) / $this->landCalculator->getTotalLand());
    }
}
