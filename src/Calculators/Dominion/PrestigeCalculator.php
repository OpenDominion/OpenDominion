<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GovernmentService;

class PrestigeCalculator
{
    /**
     * @var float Used to cap prestige gain formula
     */
    protected const PRESTIGE_CAP = 70;

    /**
     * @var int Land ratio multiplier for prestige when invading successfully
     */
    protected const PRESTIGE_RANGE_MULTIPLIER = 200;

    /**
     * @var int Base prestige when invading successfully
     */
    protected const PRESTIGE_CHANGE_BASE = -115;

    /**
     * @var int Denominator for prestige gain from raw land total
     */
    protected const PRESTIGE_LAND_FACTOR = 100;

    /**
     * @var int Base prestige gain from raw land total
     */
    protected const PRESTIGE_LAND_BASE = -750;

    /**
     * @var float Base prestige % change for both parties when invading
     */
    protected const PRESTIGE_LOSS_PERCENTAGE = 5.0;

    /**
     * @var float Additional prestige % change for defender from recent invasions
     */
    protected const PRESTIGE_LOSS_PERCENTAGE_PER_INVASION = 1.0;

    /**
     * @var float Maximum prestige % change for defender
     */
    protected const PRESTIGE_LOSS_PERCENTAGE_CAP = 15.0;

    /** @var GovernmentService */
    protected $governmentService;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * PrestigeCalculator constructor.
     */
    public function __construct(
        GovernmentService $governmentService,
        LandCalculator $landCalculator,
        MilitaryCalculator $militaryCalculator
    )
    {
        $this->governmentService = $governmentService;
        $this->landCalculator = $landCalculator;
        $this->militaryCalculator = $militaryCalculator;
    }

    /**
     * Returns the Dominion's prestige multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPrestigeMultiplier(Dominion $dominion): float
    {
        return ($dominion->prestige / 10000);
    }

    public function getPrestigeGain(Dominion $dominion, Dominion $target): int
    {
        return (int)round($this->getPrestigeGainRaw($dominion, $target) * $this->getPrestigeGainMultiplier($dominion, $target));
    }

    public function getPrestigeGainRaw(Dominion $dominion, Dominion $target): float
    {
        $attackerLand = $this->landCalculator->getTotalLand($dominion);
        $defenderLand = $this->landCalculator->getTotalLand($target);
        $range = ($defenderLand / $attackerLand);

        $prestigeGain = min(
            ($range * static::PRESTIGE_RANGE_MULTIPLIER) + static::PRESTIGE_CHANGE_BASE, // Gained through invading
            static::PRESTIGE_CAP // But capped at 92.5%
        ) + (
            max(0, $defenderLand + static::PRESTIGE_LAND_BASE) / static::PRESTIGE_LAND_FACTOR // Bonus for land size of target
        );

        // Heroes
        if ($dominion->hero !== null && $dominion->hero->getPerkValue('retal_prestige') && $target->realm->number != '0') {
            $hoursSinceInvasion = $this->militaryCalculator->getRetaliationHours($target->realm, $dominion->realm);
            if ($hoursSinceInvasion !== null) {
                $bonusPrestige = $dominion->hero->getPerkValue('retal_prestige');
                if ($hoursSinceInvasion < 24) {
                    $bonusPrestige *= 2;
                }
                $prestigeGain += $bonusPrestige;
            }
        }

        return $prestigeGain;
    }

    public function getPrestigeGainMultiplier(Dominion $dominion, Dominion $target): float
    {
        $multiplier = 1;

        // Morale
        $multiplier -= ((100 - $dominion->morale) / 100);

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('prestige_gains');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('prestige_gains');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('prestige_gains');

        // War Bonus
        if ($this->governmentService->isMutualWarEscalated($dominion->realm, $target->realm)) {
            $multiplier += 0.2;
        } elseif ($this->governmentService->isWarEscalated($dominion->realm, $target->realm) || $this->governmentService->isWarEscalated($target->realm, $dominion->realm)) {
            $multiplier += 0.1;
        }

        return $multiplier;
    }

    public function getPrestigePenalty(Dominion $dominion, Dominion $target): int
    {
        $attackerLand = $this->landCalculator->getTotalLand($dominion);
        $defenderLand = $this->landCalculator->getTotalLand($target);
        $range = ($defenderLand / $attackerLand);

        $prestigeLoss = ($dominion->prestige * -(static::PRESTIGE_LOSS_PERCENTAGE / 100));
        if ($target->user_id !== null && $range < 0.60) {
            $scalingPrestigeLoss = 16 / ($range ** 2);
            $prestigeLoss = -min($dominion->prestige, max(-$prestigeLoss, $scalingPrestigeLoss));
        }

        return (int)round($prestigeLoss);
    }

    public function getPrestigeLoss(Dominion $target, ?int $prestigeGain = null): int
    {
        $weeklyInvadedCount = $this->militaryCalculator->getRecentlyInvadedCount($target, 24 * 7, true);

        // Calculate base prestige loss
        $baseLoss = $target->prestige * (static::PRESTIGE_LOSS_PERCENTAGE / 100);

        // Cap at prestige gain if provided
        if ($prestigeGain !== null) {
            $baseLoss = min($baseLoss, $prestigeGain);
        }

        // Calculate additional loss from invasions
        $additionalLossPercentage = (static::PRESTIGE_LOSS_PERCENTAGE_PER_INVASION / 100) * $weeklyInvadedCount;
        $additionalLoss = $target->prestige * $additionalLossPercentage;

        // Calculate total loss
        $maxLoss = $target->prestige * (static::PRESTIGE_LOSS_PERCENTAGE_CAP / 100);
        $totalLoss = min($baseLoss + $additionalLoss, $maxLoss);

        return (int)round(-$totalLoss);
    }
}
