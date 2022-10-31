<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;

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
    protected const SPY_RESILIENCE_GAIN = 10;
    protected const WIZARD_RESILIENCE_GAIN = 10;

    /**
     * OpsCalculator constructor.
     *
     * @param MilitaryCalculator $militaryCalculator
     * @param RangeCalculator $rangeCalculator
     */
    public function __construct(
        GovernmentService $governmentService,
        GuardMembershipService $guardMembershipService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator,
        RangeCalculator $rangeCalculator
    )
    {
        $this->governmentService = $governmentService;
        $this->guardMembershipService = $guardMembershipService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
        $this->rangeCalculator = $rangeCalculator;
    }

    /**
     * Returns the chance of success for an info operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function infoOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $relativeRatio = clamp($selfRatio / $targetRatio, 0.1, 5);
        return 0.8 ** (2 / (($relativeRatio * 1.4) ** 1.2));
    }

    /**
     * Returns the chance of success for a theft operation.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function theftOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $relativeRatio = clamp($selfRatio / $targetRatio, 0.25, 10);
        return 0.6 ** (2 / (($relativeRatio * 1.2) ** 1.2));
    }

    /**
     * Returns the chance of success for a hostile operation or spell.
     *
     * @param float $selfRatio
     * @param float $targetRatio
     * @return float
     */
    public function blackOperationSuccessChance(float $selfRatio, float $targetRatio): float
    {
        $relativeRatio = clamp($selfRatio / $targetRatio, 0.01, 2.5);
        return (-0.15 * $relativeRatio**2) + (0.75 * $relativeRatio) + 0.05;
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

        // Guilds
        $guildSpyCasualtyReduction = 2.5;
        $guildSpyCasualtyReductionMax = 25;

        $spiesKilledMultiplier = (1 - min(
            (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $guildSpyCasualtyReduction),
            ($guildSpyCasualtyReductionMax / 100)
        ));

        // Techs
        $spiesKilledMultiplier += $dominion->getTechPerkMultiplier('spy_losses');

        // Mutual War
        if ($this->governmentService->isAtMutualWar($dominion->realm, $target->realm)) {
            $spiesKilledMultiplier *= 0.8;
        }

        return ($spiesKilledPercentage / 100) * $spiesKilledMultiplier;
    }

    /**
     * Returns the percentage of assassins killed after a failed operation.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @return float
     */
    public function getAssassinLosses(Dominion $dominion, Dominion $target, string $type): float
    {
        return $this->getSpyLosses($dominion, $target, $type) / 2;
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

        // Guilds
        $guildCasualtyReduction = 2.5;
        $guildWizardCasualtyReductionMax = 25;

        $wizardsKilledMultiplier = (1 - min(
            (($dominion->building_wizard_guild / $this->landCalculator->getTotalLand($dominion)) * $guildCasualtyReduction),
            ($guildWizardCasualtyReductionMax / 100)
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
     * @param float $modifier
     * @return int
     */
    public function getInfamyGain(Dominion $dominion, Dominion $target, string $type, float $modifier = 1): int
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

        $successRate = $this->blackOperationSuccessChance($selfRatio, $targetRatio);
        if ($successRate < 0.7 && $successRate >= 0.6) {
            $infamy += 15;
        } elseif ($successRate < 0.6 && $successRate >= 0.5) {
            $infamy += 30;
        } elseif ($successRate < 0.5 && $successRate >= 0.4) {
            $infamy += 40;
        } elseif ($successRate < 0.4) {
            $infamy += 50;
        }

        $range = $this->rangeCalculator->getDominionRange($dominion, $target);
        if ($range >= 75) {
            $infamy += 10;
        }

        return round($infamy * $modifier);
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

        if ($this->guardMembershipService->isBlackGuardMember($dominion)) {
            $decay += 5;
        }

        // TODO: Placeholder for tech perk

        $masteryCombined = min($dominion->spy_mastery, 500) + min($dominion->wizard_mastery, 500);
        $minInfamy = floor($masteryCombined / 100) * 50;
        if ($dominion->infamy + $decay < $minInfamy) {
            return $minInfamy - $dominion->infamy;
        }

        return max($decay, -$dominion->infamy);
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
            if ($dominion->spy_resilience + $resilience > 1000) {
                return 1000 - $dominion->spy_resilience;
            }
        } elseif ($type == 'wizard') {
            $resilience = static::WIZARD_RESILIENCE_GAIN;
            if ($dominion->wizard_resilience + $resilience > 1000) {
                return 1000 - $dominion->wizard_resilience;
            }
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
            $resilience = $dominion->spy_resilience;
        } elseif ($type == 'wizard') {
            $decay = static::WIZARD_RESILIENCE_DECAY;
            $resilience = $dominion->wizard_resilience;
        } else {
            return 0;
        }

        // TODO: Placeholder for tech perk

        return max($decay, -$resilience);
    }

    /**
     * Returns the damage reduction from defensive ratio.
     *
     * @param Dominion $dominion
     * @param string $type
     * @return float
     */
    public function getDamageReduction(Dominion $dominion, string $type): float
    {
        $ratio = 0;

        if ($type == 'spy') {
            $ratio = $this->militaryCalculator->getSpyRatio($dominion, 'defense');
        } elseif ($type == 'wizard') {
            $ratio = $this->militaryCalculator->getWizardRatio($dominion, 'defense');
        }

        if ($ratio == 0) {
            return 0;
        }

        return min(1, $ratio) / 2;
    }

    /**
     * Returns the amount of mastery gained by a Dominion.
     *
     * @param Dominion $dominion
     * @param Dominion $target
     * @param string $type
     * @param float $modifier
     * @return int
     */
    public function getMasteryGain(Dominion $dominion, Dominion $target, string $type, float $modifier = 1): int
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

        $mastery = round($this->getInfamyGain($dominion, $target, $type, $modifier) / 10);
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
