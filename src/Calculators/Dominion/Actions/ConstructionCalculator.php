<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;

class ConstructionCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * ConstructionCalculator constructor.
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        HeroCalculator $heroCalculator,
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator
    )
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->heroCalculator = $heroCalculator;
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Returns the Dominion's discounted construction cost.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getDiscountedLandMultiplier(Dominion $dominion): float
    {
        return clamp(1 - (0.01 * ($dominion->round->daysInRound() + 30)), 0.30, 0.50);
    }

    /**
     * Returns the Dominion's construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        return floor($this->getPlatinumCostRaw($dominion) * $this->getPlatinumCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCostRaw(Dominion $dominion): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($dominion->stat_total_land_lost >= $dominion->stat_total_land_conquered) {
            $conqueredLand = 0;
            $exploredLand = $totalLand - 250 + max(0, $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost);
        } else {
            $conqueredLand = $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost;
            $exploredLand = $totalLand - 250 - $conqueredLand;
        }

        $platinum = 850 + $conqueredLand + (1.25 * $exploredLand);

        return round($platinum);
    }

    /**
     * Returns the Dominion's construction platinum cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumCostMultiplier(Dominion $dominion): float
    {
        $multiplier = $this->getCostMultiplier($dominion);

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('construction_cost');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('construction_cost');
        $multiplier += $dominion->getTechPerkMultiplier('construction_platinum_cost');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'construction_cost');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('construction_cost');

        // Cap at -80%
        return max($multiplier, 0.2);
    }

    /**
     * Returns the Dominion's construction platinum cost for a given number of acres.
     *
     * @param Dominion $dominion
     * @param int $acres
     * @return int
     */
    public function getTotalPlatinumCost(Dominion $dominion, int $acres): int
    {
        $platinumCost = $this->getPlatinumCost($dominion);
        $totalPlatinumCost = $platinumCost * $acres;

        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        if ($discountedAcres > 0) {
            $discountedLandMultiplier = $this->getDiscountedLandMultiplier($dominion);
            $totalPlatinumCost -= (int)ceil($platinumCost * $discountedAcres * (1 - $discountedLandMultiplier));
        }

        return $totalPlatinumCost;
    }

    /**
     * Returns the Dominion's construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCost(Dominion $dominion): int
    {
        return floor($this->getLumberCostRaw($dominion) * $this->getLumberCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCostRaw(Dominion $dominion): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($dominion->stat_total_land_lost >= $dominion->stat_total_land_conquered) {
            $conqueredLand = 0;
            $exploredLand = $totalLand - 250 + max(0, $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost);
        } else {
            $conqueredLand = $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost;
            $exploredLand = $totalLand - 250 - $conqueredLand;
        }

        $lumber = 87.5 + ($conqueredLand / 4.25) + (0.285 * $exploredLand);

        return round($lumber);
    }

    /**
     * Returns the Dominion's construction lumber cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getLumberCostMultiplier(Dominion $dominion): float
    {
        $multiplier = $this->getCostMultiplier($dominion);

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('construction_lumber_cost');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'construction_cost');

        // Cap at -75%
        return max($multiplier, 0.25);
    }

    /**
     * Returns the Dominion's construction lumber cost for a given number of acres.
     *
     * @param Dominion $dominion
     * @param int $acres
     * @return int
     */
    public function getTotalLumberCost(Dominion $dominion, int $acres): int
    {
        $lumberCost = $this->getLumberCost($dominion);
        $totalLumberCost = $lumberCost * $acres;

        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        if ($discountedAcres > 0) {
            $discountedLandMultiplier = $this->getDiscountedLandMultiplier($dominion);
            $totalLumberCost -= (int)ceil($lumberCost * $discountedAcres * (1 - $discountedLandMultiplier));
        }

        return $totalLumberCost;
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        $discountedBuildings = 0;
        $discountedLandMultiplier = $this->getDiscountedLandMultiplier($dominion);
        $platinumToSpend = $dominion->resource_platinum;
        $lumberToSpend = $dominion->resource_lumber;
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);
        $platinumCost = $this->getPlatinumCost($dominion);
        $lumberCost = $this->getLumberCost($dominion);

        // Check for discounted acres after invasion
        if ($dominion->discounted_land > 0) {
            $maxFromDiscountedPlatinum = (int)floor($platinumToSpend / ($platinumCost * $discountedLandMultiplier));
            $maxFromDiscountedLumber = (int)floor($lumberToSpend / ($lumberCost * $discountedLandMultiplier));
            // Set the number of afforded discounted buildings
            $discountedBuildings = min(
                $maxFromDiscountedPlatinum,
                $maxFromDiscountedLumber,
                $dominion->discounted_land,
                $barrenLand
            );
            // Subtract discounted building cost from available resources
            $platinumToSpend -= (int)ceil($platinumCost * $discountedBuildings * $discountedLandMultiplier);
            $lumberToSpend -= (int)ceil($lumberCost * $discountedBuildings * $discountedLandMultiplier);
        }

        return $discountedBuildings + min(
            floor($platinumToSpend / $platinumCost),
            floor($lumberToSpend / $lumberCost),
            ($barrenLand - $discountedBuildings)
        );
    }

    /**
     * Returns the Dominion's global construction cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values (percentages)
        $factoryReduction = 5;
        $factoryReductionMax = 50;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'construction_cost') / 100;

        return $multiplier;
    }
}
