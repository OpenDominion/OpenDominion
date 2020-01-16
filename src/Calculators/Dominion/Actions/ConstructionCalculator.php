<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;

class ConstructionCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * ConstructionCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param LandCalculator $landCalculator
     */
    public function __construct(BuildingCalculator $buildingCalculator, LandCalculator $landCalculator)
    {
        $this->buildingCalculator = $buildingCalculator;
        $this->landCalculator = $landCalculator;
    }

    /**
     * Returns the Dominion's construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        return ($this->getPlatinumCostRaw($dominion) * $this->getPlatinumCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw construction platinum cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCostRaw(Dominion $dominion): int
    {
        $platinum = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings($dominion);
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $platinum += max(
            max($totalBuildings, 250),
            (3 * $totalLand) / 4
        );

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

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
        $multiplier *= (1 + $dominion->race->getPerkMultiplier('construction_cost'));

        // Techs
        $multiplier *= (1 + $dominion->getTechPerkMultiplier('construction_cost'));

        return $multiplier;
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
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        $platinumCost = $this->getPlatinumCost($dominion);
        $totalPlatinumCost = $platinumCost * $acres;

        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        $reboundAcres = min(
            max($dominion->discounted_land - $this->landCalculator->getTotalBarrenLand($dominion), 0),
            $acres
        );
        if ($discountedAcres > 0) {
            $totalPlatinumCost -= (int)ceil(($platinumCost * $discountedAcres) / 2);
            $totalPlatinumCost -= (int)ceil(($platinumCost * $reboundAcres) / 4);
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
        return ($this->getLumberCostRaw($dominion) * $this->getLumberCostMultiplier($dominion));
    }

    /**
     * Returns the Dominion's raw construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCostRaw(Dominion $dominion): int
    {
        $lumber = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings($dominion);
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        $lumber += max(
            max($totalBuildings, 250),
            (3 * $totalLand) / 4
        );

        $lumber -= 250;
        $lumber *= 0.35;
        $lumber += 87.5;

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
        return $this->getCostMultiplier($dominion);
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
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);

        $lumberCost = $this->getLumberCost($dominion);
        $totalLumberCost = $lumberCost * $acres;

        // Check for discounted acres after invasion
        $discountedAcres = min($dominion->discounted_land, $acres);
        $reboundAcres = min(
            max($dominion->discounted_land - $this->landCalculator->getTotalBarrenLand($dominion), 0),
            $acres
        );
        if ($discountedAcres > 0) {
            $totalLumberCost -= (int)ceil(($lumberCost * $discountedAcres) / 2);
            $totalLumberCost -= (int)ceil(($lumberCost * $reboundAcres) / 4);
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
        $reboundBuildings = 0;
        $discountedBuildings = 0;
        $platinumToSpend = $dominion->resource_platinum;
        $lumberToSpend = $dominion->resource_lumber;
        $barrenLand = $this->landCalculator->getTotalBarrenLand($dominion);
        $platinumCost = $this->getPlatinumCost($dominion);
        $lumberCost = $this->getLumberCost($dominion);

        // Check for discounted acres after invasion
        if ($dominion->discounted_land > 0) {
            //  25% cost acres regained after lost due to invasion
            $reboundAcres = max($dominion->discounted_land - $barrenLand, 0);
            $maxFromDiscountedPlatinum = (int)floor($platinumToSpend / ($platinumCost / 4));
            $maxFromDiscountedLumber = (int)floor($lumberToSpend / ($lumberCost / 4));
            // Set the number of afforded discounted buildings
            $reboundBuildings = min(
                $maxFromDiscountedPlatinum,
                $maxFromDiscountedLumber,
                $reboundAcres,
                $barrenLand
            );
            // Subtract discounted building cost from available resources
            $platinumToSpend -= (int)ceil(($platinumCost * $reboundBuildings) / 4);
            $lumberToSpend -= (int)ceil(($lumberCost * $reboundBuildings) / 4);

            // 50% cost acres from invasion
            $discountedAcres = max($dominion->discounted_land - $reboundAcres, 0);
            $maxFromDiscountedPlatinum = (int)floor($platinumToSpend / ($platinumCost / 2));
            $maxFromDiscountedLumber = (int)floor($lumberToSpend / ($lumberCost / 2));
            // Set the number of afforded discounted buildings
            $discountedBuildings = min(
                $maxFromDiscountedPlatinum,
                $maxFromDiscountedLumber,
                $discountedAcres,
                max($barrenLand - $reboundAcres, 0)
            );
            // Subtract discounted building cost from available resources
            $platinumToSpend -= (int)ceil(($platinumCost * $discountedBuildings) / 2);
            $lumberToSpend -= (int)ceil(($lumberCost * $discountedBuildings) / 2);
        }

        return $reboundBuildings + $discountedBuildings + min(
                floor($platinumToSpend / $platinumCost),
                floor($lumberToSpend / $lumberCost),
                ($barrenLand - $reboundBuildings - $discountedBuildings)
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
        $factoryReduction = 4;
        $factoryReductionMax = 75;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        return $multiplier;
    }
}
