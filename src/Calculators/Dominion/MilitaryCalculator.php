<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class MilitaryCalculator
{
    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var QueueService */
    protected $queueService;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * MilitaryCalculator constructor.
     *
     * @param ImprovementCalculator $improvementCalculator
     * @param LandCalculator $landCalculator
     * @param QueueService $queueService
     * @param SpellCalculator $spellCalculator
     */
    public function __construct(
        ImprovementCalculator $improvementCalculator,
        LandCalculator $landCalculator,
        QueueService $queueService,
        SpellCalculator $spellCalculator
    ) {
        $this->improvementCalculator = $improvementCalculator;
        $this->landCalculator = $landCalculator;
        $this->queueService = $queueService;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Returns the Dominion's offensive power.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getOffensivePower(Dominion $dominion): float
    {
        $op = ($this->getOffensivePowerRaw($dominion) * $this->getOffensivePowerMultiplier($dominion));

        return ($op * $this->getMoraleMultiplier($dominion));
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

        return $op;
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

        // Values (percentages)
        $opPerGryphonNest = 1.75;
        $gryphonNestMaxOp = 35;
        $spellCrusade = 5;
        $spellKillingRage = 10;

        // Gryphon Nests
        $multiplier += min(
            (($opPerGryphonNest * $dominion->building_gryphon_nest) / $this->landCalculator->getTotalLand($dominion)),
            ($gryphonNestMaxOp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('offense');

        // Improvement: Forges
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'forges');

        // Racial Spell
        // todo
        // Spell: Warsong (Sylvan) (+10%)
        // Spell: Howling (+10%)
        // Spell: Nightfall (+5%)
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, [
            'crusade' => $spellCrusade,
            'killing_rage' => $spellKillingRage,
        ]);

        // Prestige
        $multiplier += ((($dominion->prestige / 250) * 2.5) / 100);

        // Tech: Military (+5%)
        // Tech: Magical Weaponry (+10%)
        // todo

        return (1 + $multiplier);
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
     * @param float $multiplierReduction
     * @return float
     */
    public function getDefensivePower(Dominion $dominion, float $multiplierReduction = 0): float
    {
        $dp = ($this->getDefensivePowerRaw($dominion) * $this->getDefensivePowerMultiplier($dominion, $multiplierReduction));

        return ($dp * $this->getMoraleMultiplier($dominion));
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
        $minDPPerAcre = 1.5;
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
        ); // todo: recheck this

        return max(
            $dp,
            ($minDPPerAcre * $this->landCalculator->getTotalLand($dominion))
        );
    }

    /**
     * Returns the Dominion's defensive power multiplier.
     *
     * @param Dominion $dominion
     * @param float $multiplierReduction
     * @return float
     */
    public function getDefensivePowerMultiplier(Dominion $dominion, float $multiplierReduction = 0): float
    {
        $multiplier = 0;

        // Values (percentages)
        $dpPerGuardTower = 1.75;
        $guardTowerMaxDp = 35;
        $spellAresCall = 10;

        // Guard Towers
        $multiplier += min(
            (($dpPerGuardTower * $dominion->building_guard_tower) / $this->landCalculator->getTotalLand($dominion)),
            ($guardTowerMaxDp / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('defense');

        // Improvement: Walls
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'walls');

        // Spell: Frenzy (Halfling) (+20%)
        // Spell: Blizzard (+15%)
        // Spell: Howling (+10%)
        // todo

        // Spell: Ares' Call (+10%)
        $multiplier += $this->spellCalculator->getActiveSpellMultiplierBonus($dominion, 'ares_call', $spellAresCall);

        // Multiplier reduction when we want to factor in temples from another
        // dominion
        $multiplier = max(($multiplier - $multiplierReduction), 0);

        return (1 + $multiplier);
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
     * Returns the Dominion's morale modifier for OP/DP.
     *
     * Net OP/DP gets lowered linearly by up to -10% at 0% morale.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getMoraleMultiplier(Dominion $dominion): float
    {
        return clamp((0.9 + ($dominion->morale / 1000)), 0.9, 1.0);
    }

    /**
     * Returns the Dominion's spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatio(Dominion $dominion): float
    {
        return ($this->getSpyRatioRaw($dominion) * $this->getSpyRatioMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw spy ratio.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioRaw(Dominion $dominion): float
    {
        $spies = $dominion->military_spies;

        // Add units which count as (partial) spies (Lizardfolk Chameleon)
        foreach ($dominion->race->units as $unit) {
            if ($unit->perkType === null) {
                continue;
            }

            if ($unit->perkType->key === 'counts_as_spy') {
                $spies += floor($dominion->{"military_unit{$unit->slot}"} * (float)$unit->unit_perk_type_values);
            }
        }

        return ($spies / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's spy ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpyRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('spy_strength');

        // Wonder: Great Oracle (+30%)
        // todo

        return (1 + $multiplier);
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
        return (($dominion->military_wizards + ($dominion->military_archmages * 2)) / $this->landCalculator->getTotalLand($dominion));
    }

    /**
     * Returns the Dominion's wizard ratio multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getWizardRatioMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Racial bonus
        $multiplier += $dominion->race->getPerkMultiplier('wizard_strength');

        // Improvement: Towers
        $multiplier += $this->improvementCalculator->getImprovementMultiplierBonus($dominion, 'towers');

        // Tech: Magical Weaponry  (+15%)
        // todo

        return (1 + $multiplier);
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

    /**
     * Gets the total amount of living specialist/elite units for a Dominion.
     *
     * Total amount includes units at home and units returning from battle.
     *
     * @param Dominion $dominion
     * @param int $slot
     * @return int
     */
    public function getTotalUnitsForSlot(Dominion $dominion, int $slot): int
    {
        return (
            $dominion->{'military_unit' . $slot} +
            $this->queueService->getInvasionQueueTotalByResource($dominion, "unit{$slot}")
        );
    }

    /**
     * Returns the number of time the Dominion was recently invaded.
     *
     * 'Recent' refers to the past 24 hours.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getRecentlyInvadedCount(Dominion $dominion): int
    {
        // todo
        return 0;
    }
}
