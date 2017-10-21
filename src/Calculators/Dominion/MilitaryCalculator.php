<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

class MilitaryCalculator
{
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * MilitaryCalculator constructor.
     *
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(ImprovementCalculator $improvementCalculator, LandCalculator $landCalculator)
    {
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePower(Dominion $dominion): float
    {
        return ($this->getOffensivePowerRaw($dominion) * $this->getOffensivePowerMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw offensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRaw(Dominion $dominion): float
    {
        $op = 0;

        foreach ($dominion->race->units as $unit) {
            $op += ($dominion->{'military_unit' . $unit->slot} * $unit->power_offense);
        }

        return (float)$op;
    }

    /**
     * Returns the Dominion's offensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentage)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;

        // Gryphon Nests
        $multiplier += min(
            (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
            ($gryphonNestMaxOp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Improvement: Forges
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'forges');

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

    /**
     * Returns the Dominion's offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getOffensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw offensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getOffensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's defensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePower(Dominion $dominion): float
    {
        return ($this->getDefensivePowerRaw($dominion) * $this->getDefensivePowerMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRaw(Dominion $dominion): float
    {
        $dp = 0;

        // Values
        $dpPerDraftee = 1;
        $forestHavenDpPerPeasant = 0.75;
        $peasantsPerForestHaven = 20;

        // Military
        foreach ($dominion->race->units as $unit) {
            $dp += ($dominion->{'military_unit' . $unit->slot} * $unit->power_defense);
        }

        // Draftees
        $dp += ($dominion->military_draftees * $dpPerDraftee);

        // Forest Havens
        $dp += min(
            ($dominion->peasants * $forestHavenDpPerPeasant),
            ($dominion->building_forest_haven * $forestHavenDpPerPeasant * $peasantsPerForestHaven)
        );

        return (float)$dp;
    }

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;

        // Guard Towers
        $multiplier += min(
            (($dpPerGuardTower * $dominion->building_guard_tower) / $this->landCalculator->getTotalLand($dominion)),
            ($guardTowerMaxDp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Improvement: Walls
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'walls');

        // Spell: Frenzy (Halfling) (+20%)
        // Spell: Blizzard (+15%)
        // Spell: Howling (+10%)
        // Spell: Ares' Call (+10%)
        // todo

        return (float)(1 + $multiplier);
    }

    /**
     * Returns the Dominion's defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatio(Dominion $dominion): float
    {
        return ($this->getDefensivePower($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's raw defensive power ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDefensivePowerRatioRaw(Dominion $dominion): float
    {
        return ($this->getDefensivePowerRaw($dominion) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatio(Dominion $dominion): float
    {
        return $this->getSpyRatioRaw($dominion);
        // todo: racial spy strength multiplier
    }

    /**
     * Returns the Dominion's raw spy ratio per acre of land.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion): float
    {
        return (float)($dominion->military_spies / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyStrengthRegen(Dominion $dominion): float
    {
        $regen = 4;

        // todo: Spy Master / Dark Artistry tech
        // todo: check if this needs to be a float

        return (float)$regen;
    }

    /**
     * Returns the Dominion's wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatio(Dominion $dominion): float
    {
        return ($this->getWizardRatioRaw($dominion) * $this->getWizardRatioMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw wizard ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioRaw(Dominion $dominion): float
    {
        return (float)(($dominion->military_wizards + ($dominion->military_archmages * 2)) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's wizard ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvement: Towers
        $multiplier += $this->improvementCalculator->getImprovementMultiplier($dominion, 'towers');

        // Tech: Magical Weaponry  (+15%)
        // todo

        return (float)$multiplier;
    }

    /**
     * Returns the Dominion's wizard strength regeneration.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardStrengthRegen(Dominion $dominion): float
    {
        $regen = 5;

        // todo: Master of Magi / Dark Artistry tech
        // todo: check if this needs to be a float

        return (float)$regen;
    }
}
