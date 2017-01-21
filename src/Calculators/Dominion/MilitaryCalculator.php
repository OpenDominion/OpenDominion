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
        // =E3*Overview!$B$22+F3*Overview!$B$23+G3*(VLOOKUP(Overview!$A$24,Races!$D$4:$H$108,2,FALSE)+$CH$7*BY3)+H3*(VLOOKUP(Overview!$A$25,Races!$D$4:$H$108,2,FALSE)+$CH$9*CD3+$CH$4*BU3)+$CH$8*CB3
    }

    public function getOffensivePowerMultiplier()
    {
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
        // =E3*Overview!$C$22+F3*Overview!$C$23+G3*(VLOOKUP(Overview!$A$24,Races!$D$4:$H$108,3,FALSE)+$CH$4*BT3+$CH$5*BW3+$CH$6*BX3+$CH$7*BZ3+$CH$9*CC3+$CH$10*CE3+$CH$11*CF3)+H3*(VLOOKUP(Overview!$A$25,Races!$D$4:$H$108,3,FALSE)+$CH$4*BV3) + MIN(Population!C3*Constants!B$13,Constants!B$13*20*Construction!AY3)+$CH$8*CA3
        // + draftees * dp_per_draftee (1)
    }

    public function getDefensivePowerMultiplier()
    {
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
