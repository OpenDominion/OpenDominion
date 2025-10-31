<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;

class ExplorationCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /**
     * ExplorationCalculator constructor.
     *
     * @param HeroCalculator $heroCalculator
     * @param LandCalculator $landCalculator
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(
        HeroCalculator $heroCalculator,
        LandCalculator $landCalculator,
        GuardMembershipService $guardMembershipService
    )
    {
        $this->heroCalculator = $heroCalculator;
        $this->landCalculator = $landCalculator;
        $this->guardMembershipService = $guardMembershipService;
    }

    /**
     * Returns the Dominion's exploration platinum cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $platinum = (0.00045 * ($totalLand ** 2)) + (5.45 * $totalLand) - 800;

        return round($platinum * $this->getPlatinumCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's exploration platinum cost multiplier.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('explore_platinum_cost');

        // Techs
        $techBonus = $dominion->getTechPerkMultiplier('explore_platinum_cost');
        $excludedRaces = ['firewalker', 'goblin', 'lycanthrope', 'vampire'];
        if ($techBonus != 0 && in_array($dominion->race->key, $excludedRaces)) {
            // Bonus is halved for these races
            $techBonus *= 0.5;
        }
        $multiplier += $techBonus;

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('explore_platinum_cost');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'explore_cost');

        // Elite Guard Tax
        if ($this->guardMembershipService->isEliteGuardMember($dominion) && $dominion->user_id !== null) {
            $multiplier += 0.25;
        }

        // Delve into Shadow
        $masteryPerk = $dominion->getSpellPerkValue('explore_cost_wizard_mastery');
        if ($masteryPerk) {
            $multiplier -= min(1000, $dominion->wizard_mastery) / $masteryPerk / 100;
        }

        return $multiplier;
    }

    /**
     * Returns the Dominion's exploration draftee cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getDrafteeCost(Dominion $dominion): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $drafteeCost = rfloor($totalLand / 150) + 3;

        // Techs
        $drafteeCost += $dominion->getTechPerkValue('explore_draftee_cost');

        return $drafteeCost;
    }

    /**
     * Returns the maximum number of acres of land a Dominion can afford to
     * explore.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        return min(
            rfloor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            rfloor($dominion->military_draftees / $this->getDrafteeCost($dominion))
        );
    }

    /**
     * Returns the morale drop after exploring for $amount of acres of land.
     *
     * @param Dominion $dominion
     * @param int $amount
     * @return int
     */
    public function getMoraleDrop(Dominion $dominion, $amount): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $moraleDrop = max(1, rfloor(($amount + 2) / 3));

        return $moraleDrop;
    }
}
