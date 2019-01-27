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

        $multiplier += $dominion->race->getPerkMultiplier('construction_cost');

        return $multiplier;
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
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        return min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->resource_lumber / $this->getLumberCost($dominion)),
            $this->landCalculator->getTotalBarrenLand($dominion)
        );
    }

    /**
     * Returns the Dominion's global construction cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    protected function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $factoryReduction = 4;
        $factoryReductionMax = 75;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        return (1 + $multiplier);
    }
}
