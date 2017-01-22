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
            $op += ($this->dominion->{'military_unit' . $unit->slot} * $unit->power_offense);
        }

        return (float)$op;
    }

    public function getOffensivePowerMultiplier()
    {
        $multiplier = 0;

        // Values (percentage)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;

        // todo: Racial Bonus

        // Gryphon Nests
        $multiplier += min(
            (($opPerGryphonNest * $this->dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand()),
            ($gryphonNestMaxOp / 100)
        );

        // Spell: Warsong (Sylvan) (+10%)
        // Spell: Howling (+10%)
        // Spell: Nightfall (+5%)
        // todo

        // Prestige
        $multiplier += ((($this->dominion->prestige / 250) * 2.5) / 100);

        // Tech: Military (+5%)
        // Tech: Magical Weaponry (+10%)
        // todo

        return (float)(1 + $multiplier);
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
            $dp += ($this->dominion->{'military_unit' . $unit->slot} * $unit->power_defense);
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
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;

        // Racial Bonus
        // todo

        // Improvement: Walls
        // todo

        // Guard Towers
        $multiplier += min(
            (($dpPerGuardTower * $this->dominion->building_guard_tower) / $this->landCalculator->getTotalLand()),
            ($guardTowerMaxDp / 100)
        );

        // Spell: Frenzy (Halfling) (+20%)
        // Spell: Blizzard (+15%)
        // Spell: Howling (+10%)
        // Spell: Ares' Call (+10%)
        // todo

        return (float)(1 + $multiplier);
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

//    public function getSpyStrengthRegen()
//    {
//        $regen = 4;
//
//        // todo: Spy Master / Dark Artistry tech
//
//        return $regen;
//    }

    public function getWizardRatio()
    {
        return $this->getWizardRatioRaw();
        // todo: racial multiplier + Magical Weaponry tech (+15%)
    }

    public function getWizardRatioRaw()
    {
        return (float)(($this->dominion->military_wizards + ($this->dominion->military_archmages * 2)) / $this->landCalculator->getTotalLand());
    }

//    public function getWizardStrengthRegen()
//    {
//        $regen = 5;
//
//        // todo: Master of Magi / Dark Artistry tech
//
//        return $regen;
//    }
}
