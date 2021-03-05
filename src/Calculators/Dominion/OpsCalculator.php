<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GovernmentService;

class OpsCalculator
{
    /**
     * @var float Base amount of infamy lost each hour
     */
    protected const INFAMY_DECAY_BASE = -20;

    /**
     * @var float Base amount of resilience lost each hour
     */
    protected const SPY_RESILIENCE_DECAY = -8;
    protected const WIZARD_RESILIENCE_DECAY = -8;

    /**
     * @var float Base amount of resilience gained per op
     */
    protected const SPY_RESILIENCE_GAIN = 8;
    protected const WIZARD_RESILIENCE_GAIN = 11;

    /**
     * OpsCalculator constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(
        GovernmentService $governmentService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->governmentService = $governmentService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Returns the chance of success for an info operation or spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function infoOperationSuccessChance(Dominion $dominion, ?Dominion $target, string $type): float
    {
        $ratio = $this->getRelativeRatio($dominion, $target, $type);
        $successRate = 0.8 ** (2 / (($ratio * 1.4) ** 1.2));
        return clamp($successRate, 0.01, 0.99);
    }

    /**
     * Returns the chance of success for a theft operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function theftOperationSuccessChance(Dominion $dominion, ?Dominion $target, string $type): float
    {
        $ratio = $this->getRelativeRatio($dominion, $target, $type);
        $successRate = 0.6 ** (2 / (($ratio * 1.2) ** 1.2));
        return clamp($successRate, 0.01, 0.99);
    }

    /**
     * Returns the chance of success for a hostile operation or spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function blackOperationSuccessChance(Dominion $dominion, ?Dominion $target, string $type): float
    {
        $ratio = $this->getRelativeRatio($dominion, $target, $type);
        $successRate = (1 / (1 + exp(($ratio ** -0.4) - $ratio))) + (0.008 * $ratio) - 0.07;
        return clamp($successRate, 0.01, 0.95);
    }

    /**
     * Returns the relative ratio of two dominions.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getRelativeRatio(Dominion $dominion, Dominion $target, string $type): float
    {
        $selfRatio = 0;
        $targetRatio = 1;

        if ($type == 'spy') {
            $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
        } elseif ($type == 'wizard') {
            $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
        }

        return $selfRatio / $targetRatio;
    }

    /**
     * Returns the percentage of spies killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getSpyLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'info') {
            $spiesKilledBasePercentage = 0.25;
            $min = 0.25;
            $max = 1;
        } elseif ($type == 'theft') {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            $spiesKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        }

        $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');

        $spyLossSpaRatio = ($targetRatio / $selfRatio);
        $spiesKilledPercentage = clamp($spiesKilledBasePercentage * $spyLossSpaRatio, $min, $max);

        // Forest Havens
        $forestHavenSpyCasualtyReduction = 3;
        $forestHavenSpyCasualtyReductionMax = 30;

        $spiesKilledMultiplier = (1 - min(
            (($dominion->building_forest_haven / $this->landCalculator->getTotalLand($dominion)) * $forestHavenSpyCasualtyReduction),
            ($forestHavenSpyCasualtyReductionMax / 100)
        ));

        // Techs
        $spiesKilledMultiplier *= (1 + $dominion->getTechPerkMultiplier('spy_losses'));

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $spiesKilledMultiplier *= 0.8;
        }

        return ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
    }

    /**
     * Returns the percentage of wizards killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getWizardLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        // Values (percentage)
        if ($type == 'hostile') {
            $wizardsKilledBasePercentage = 1;
            $min = 0.5;
            $max = 1.5;
        } else {
            return 0;
        }

        $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
        $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');

        $wizardLossSpaRatio = ($targetRatio / $selfRatio);
        $wizardsKilledPercentage = clamp($wizardsKilledBasePercentage * $wizardLossSpaRatio, $min, $max);

        // Wizard Guilds
        $wizardGuildCasualtyReduction = 3;
        $wizardGuildWizardCasualtyReductionMax = 30;

        $wizardsKilledMultiplier = (1 - min(
            (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $wizardGuildCasualtyReduction),
            ($wizardGuildWizardCasualtyReductionMax / 100)
        ));

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $wizardsKilledMultiplier *= 0.8;
        }

        return ($wizardsKilledPercentage / 100) * $wizardsKilledMultiplier;
    }

    /**
     * Returns the percentage of archmages killed after a failed spell.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getArchmageLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getWizardLosses($dominion, $target, $type) / 10;
    }

    /**
     * Returns the amount of infamy gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getInfamyGain(Dominion $dominion, Dominion $target, string $type): int
    {
        $infamy = 5;
        if ($type == 'spy') {
            $selfRatio = $this->militaryCalculator->getSpyRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getSpyRatio($target, 'defense');
        } elseif ($type == 'wizard') {
            $selfRatio = $this->militaryCalculator->getWizardRatio($dominion, 'offense');
            $targetRatio = $this->militaryCalculator->getWizardRatio($target, 'defense');
        } else {
            return 0;
        }

        $relativeRatio = ($targetRatio / $selfRatio);
        if ($relativeRatio >= 0.7 && $relativeRatio < 0.9) {
            $infamy += 15;
        } elseif ($relativeRatio >= 0.9 && $relativeRatio < 1.1) {
            $infamy += 30;
        } elseif ($relativeRatio >= 1.1 && $relativeRatio < 1.3) {
            $infamy += 40;
        } elseif ($relativeRatio >= 1.3) {
            $infamy += 50;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        if ($range >= 75 && $range <= (10000 / 75)) {
            $infamy += 10;
        } elseif ($range >= 60 && $range <= (10000 / 60)) {
            if ($dominion->getTechPerkValue('infamy_royal_guard') !== 0) {
                $infamy += 10;
            }
        }

        return $infamy;
    }

    /**
     * Returns the Dominion's hourly infamy decay.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getInfamyDecay(Dominion $dominion): int
    {
        $decay = static::INFAMY_DECAY_BASE;

        // TODO: Placeholder for tech perk

        return $decay;
    }

    /**
     * Returns the amount of resilience gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getResilienceGain(Dominion $dominion, string $type): int
    {
        if ($type == 'spy') {
            $resilience = static::SPY_RESILIENCE_GAIN;
        } elseif ($type == 'wizard') {
            $resilience = static::WIZARD_RESILIENCE_GAIN;
        } else {
            return 0;
        }

        // TODO: Placeholder for tech perk

        return $resilience;
    }

    /**
     * Returns the Dominion's hourly resilience decay.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return int
     */
    public function getResilienceDecay(Dominion $dominion, string $type): int
    {
        if ($type == 'spy') {
            $decay = static::SPY_RESILIENCE_DECAY;
        } elseif ($type == 'wizard') {
            $decay = static::WIZARD_RESILIENCE_DECAY;
        } else {
            return 0;
        }

        // TODO: Placeholder for tech perk

        return $decay;
    }

    /**
     * Returns the damage reduction from resilience.
     *
     * @param int $resilience
     * @return float
     */
    public function getResilienceBonus(int $resilience): float
    {
        if ($resilience == 0) {
            return 0;
        }

        return (1 + error_function(0.00226 * ($resilience - 770))) / 2;
    }

    /**
     * Returns the amount of mastery gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getMasteryGain(Dominion $dominion, Dominion $target, string $type): int
    {
        if ($type == 'spy') {
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
        } elseif ($type == 'wizard') {
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
        } else {
            return 0;
        }

        $mastery = round($this->getInfamyGain($dominion, $target, $type) / 10);
        $masteryDifference = $selfMastery - $targetMastery;

        if ($masteryDifference > 500) {
            return 0;
        }
        if (abs($masteryDifference) <= 100) {
            $mastery += 1;
        }
        if ($targetMastery > $selfMastery) {
            $mastery += 1;
        }

        return $mastery;
    }

    /**
     * Returns the amount of mastery lost by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return int
     */
    public function getMasteryLoss(Dominion $dominion, Dominion $target, string $type): int
    {
        if ($type == 'spy') {
            $selfMastery = $dominion->spy_mastery;
            $targetMastery = $target->spy_mastery;
        } elseif ($type == 'wizard') {
            $selfMastery = $dominion->wizard_mastery;
            $targetMastery = $target->wizard_mastery;
        } else {
            return 0;
        }

        $mastery = 0;
        $masteryDifference = $selfMastery - $targetMastery;

        if ($targetMastery <= 100) {
            return 0;
        }
        if (abs($masteryDifference) <= 100) {
            $mastery += 1;
        }
        if ($targetMastery > $selfMastery) {
            $mastery += 1;
        }

        return $mastery;
    }
}
