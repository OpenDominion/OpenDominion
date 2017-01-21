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

    public function getOffensivePower()
    {
        return ($this->getOffensivePowerRaw() * $this->getOffensivePowerMultiplier());
    }

    public function getOffensivePowerRaw()
    {
        $op = 0;

        foreach ($this->dominion->race->units as $unit) {
            $op += $unit->power_offense;
        }

        return (float)$op;
    }

    public function getOffensivePowerMultiplier()
    {
        return 1; // todo
        // =Overview!$I$25+Imps!AB3+ROUND(MIN(Constants!$B$12*Construction!BA3/Construction!$E3,Constants!$D$12),4)+MAX(IF(Magic!$AE3>0,Constants!$F$79),IF(Magic!AG3>0,Constants!$F$81),IF(Magic!AJ3>0,Constants!$F$84)) + ROUNDDOWN(Production!O3/250*Constants!$B$90,2)/100 + MAX(Constants!$M$41*Techs!AH3,Constants!$M$55*Techs!AV3)
    }

    public function getOffensivePowerRatio()
    {
        return (float)($this->getOffensivePower() / $this->landCalculator->getTotalLand());
    }

    public function getOffensivePowerRatioRaw()
    {
        return (float)($this->getOffensivePowerRaw() / $this->landCalculator->getTotalLand());
    }

    public function getDefensivePower()
    {
        return ($this->getDefensivePowerRaw() * $this->getDefensivePowerMultiplier());
    }

    public function getDefensivePowerRaw()
    {
        $dp = 0;

        // Values
        $dpPerDraftee = 1;
        $forestHavenDpPerPeasant = 0.75;
        $peasantsPerForestHaven = 20;

        // Military
        foreach ($this->dominion->race->units as $unit) {
            $dp += $unit->power_defense;
        }

        // Draftees
        $dp += ($this->dominion->military_draftees * $dpPerDraftee);

        // Forest Havens
        $dp += min(
            ($this->dominion->peasants * $forestHavenDpPerPeasant),
            ($this->dominion->building_forest_haven * $forestHavenDpPerPeasant * $peasantsPerForestHaven)
        );

        return (float)$dp;
    }

    public function getDefensivePowerMultiplier()
    {
        return 1; // todo
        // =Overview!$I$24 + Imps!AC3 + ROUND( MIN(Constants!$B$11*Construction!BC3/Construction!$E3,Constants!$D$11), 4) + IF(Magic!AD3>0,Constants!$F$78,IF(Magic!AH3>0,Constants!$F$82,IF(Magic!AG3>0,Constants!$H$81,IF(Magic!Z3>0,Constants!$F$74))))
    }

    public function getDefensivePowerRatio()
    {
        return (float)($this->getDefensivePower() / $this->landCalculator->getTotalLand());
    }

    public function getDefensivePowerRatioRaw()
    {
        return (float)($this->getDefensivePowerRaw() / $this->landCalculator->getTotalLand());
    }

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
