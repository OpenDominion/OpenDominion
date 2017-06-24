<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\LandCalculator;
use OpenDominion\Contracts\Calculators\Dominion\MilitaryCalculator as MilitaryCalculatorContract;
use OpenDominion\Models\Dominion;

class MilitaryCalculator implements MilitaryCalculatorContract
{
    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * MilitaryCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(LandCalculator $landCalculator)
    {
        $this->landCalculator = $landCalculator;
    }

    public function getOffensivePower(Dominion $dominion)
    {
        return ($this->getOffensivePowerRaw($dominion) * $this->getOffensivePowerMultiplier($dominion));
    }

    public function getOffensivePowerRaw(Dominion $dominion)
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            $op += ($dominion->{'military_unit' . $unit->slot} * $unit->power_offense);
        }

        return (float)$op;
    }

    public function getOffensivePowerMultiplier(Dominion $dominion)
    {
        $multiplier = 0;

        // Values (percentage)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Gryphon Nests
        $multiplier += min(
            (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
            ($gryphonNestMaxOp / 100)
        );

        // Spell: Warsong (Sylvan) (+10%)
        // Spell: Howling (+10%)
        // Spell: Nightfall (+5%)
        // todo

        // Prestige
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);

        // Tech: Military (+5%)
        // Tech: Magical Weaponry (+10%)
        // todo

        return (float)(1 + $multiplier);
    }

    public function getOffensivePowerRatio(Dominion $dominion)
    {
        return (float)($this->getOffensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    public function getOffensivePowerRatioRaw(Dominion $dominion)
    {
        return (float)($this->getOffensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    public function getDefensivePower(Dominion $dominion)
    {
        return ($this->getDefensivePowerRaw($dominion) * $this->getDefensivePowerMultiplier($dominion));
    }

    public function getDefensivePowerRaw(Dominion $dominion)
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

    public function getDefensivePowerMultiplier(Dominion $dominion)
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;

        // Racial Bonus
        $multiplier += $this->dominion->race->getPerkMultiplier('defense');

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

    public function getDefensivePowerRatio(Dominion $dominion)
    {
        return (float)($this->getDefensivePower() / $this->landCalculator->getTotalLand());
    }

    public function getDefensivePowerRatioRaw(Dominion $dominion)
    {
        return (float)($this->getDefensivePowerRaw() / $this->landCalculator->getTotalLand());
    }

    public function getSpyRatio(Dominion $dominion)
    {
        return $this->getSpyRatioRaw();
        // todo: racial spy strength multiplier
    }

    public function getSpyRatioRaw(Dominion $dominion)
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

    public function getWizardRatio(Dominion $dominion)
    {
        return $this->getWizardRatioRaw();
        // todo: racial multiplier + Magical Weaponry tech (+15%)
    }

    public function getWizardRatioRaw(Dominion $dominion)
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
