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
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        $platinum = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings($dominion);
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalBuildings >= 1250) {
            $platinum += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $platinum += $totalLand;
        }

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

        return (int)round($platinum);
    }

    /**
     * Returns the Dominion's construction lumber cost (per building).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getLumberCost(Dominion $dominion): int
    {
        $lumber = 0;
        $totalBuildings = $this->buildingCalculator->getTotalBuildings($dominion);
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalBuildings >= 1250) {
            $lumber += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $lumber += $totalLand;
        }

        $lumber -= 250;
        $lumber *= 0.6;
        $lumber += 88;

        return (int)round($lumber);
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        // todo: factor in amount of barren land?
        return (int)min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->resource_lumber / $this->getLumberCost($dominion)),
            $this->landCalculator->getTotalBarrenLand($dominion)
        );
    }
}
