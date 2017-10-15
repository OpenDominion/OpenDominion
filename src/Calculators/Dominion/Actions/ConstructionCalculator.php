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

        // BlackReign's formula. Seems correct
        $platinum += max(
            (($totalBuildings < 250) ? 250 : $totalBuildings),
            (3 * $totalBuildings) / 4
        );

        // Wiki formula. >= 1250 seems correct at start, but is reportedly incorrect later in the round
//        if ($totalBuildings >= 1250) {
//            $platinum += max(
//                ($totalLand * 0.75),
//                $totalBuildings
//            );
//        } else {
//            $platinum += $totalLand;
//        }

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

        $platinum *= $this->getCostMultiplier($dominion);

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

        // BlackReign's formula. Seems correct
        $lumber += max(
            (($totalBuildings < 250) ? 250 : $totalBuildings),
            (3 * $totalBuildings) / 4
        );

        $lumber -= 250;
        $lumber *= 0.35;
        $lumber += 87.5;

        // Wiki formula. >= 1250 seems correct at start, but is reportedly incorrect later in the round
//        if ($totalBuildings >= 1250) {
//            $lumber += max(
//                ($totalLand * 0.75),
//                $totalBuildings
//            );
//        } else {
//            $lumber += $totalLand;
//        }
//
//        $lumber -= 250;
//        $lumber *= 0.6;
//        $lumber += 88;

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

    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1.0;

        // Values (percentages)
        $factoryReduction = 4;
        $factoryReductionMax = 75;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        return $multiplier;

        /*
        todo: above formula should be good, but leaving this here this in case

        buildings under construction = SUM(constructing home : constructing docks)

        =IF(
            landbonus? > 0;
            IF(
                buildings under construction > 0;
                (
                    (
                        MIN(25%; (factories + factories destroy) / (land - landbonus? * 20))
                        * (buildings under construction - landbonus? * 20)
                ) + (factories + factories destroy) / (land) * landbonus? * 20) / buildings under construction;
                (factories + factories destroy) / (land)
            );
            (factories + factories destroy) / land
        )
        */
    }
}
