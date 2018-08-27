<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class BuildingCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /**
     * BuildingCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     */
    public function __construct(BuildingHelper $buildingHelper)
    {
        $this->buildingHelper = $buildingHelper;
    }

    /**
     * Returns the Dominion's total number of constructed buildings.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalBuildings(Dominion $dominion): int
    {
        $totalBuildings = 0;

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $totalBuildings += $dominion->{'building_' . $buildingType};
        }

        return $totalBuildings;
    }

    public function getBuildingTypesToDestroy(
        Dominion $dominion, int $totalBuildingsToDestroy, string $landType): array
    {
        $buildingTypesForLandType = $this->buildingHelper->getBuildingTypesByRace($dominion->race)[$landType];

        $buildingsPerType = [];

        $totalBuildingsForLandType = 0;

        foreach($buildingTypesForLandType as $buildingType) {
            $buildingsForType = $dominion->{'building_' . $buildingType};
            $totalBuildingsForLandType += $buildingsForType;
            $buildingsPerType[$buildingType] = $buildingsForType;
        }

        $buildingsToDestroyRatio = $totalBuildingsToDestroy / $totalBuildingsForLandType;
        $totalBuildingsDestroyed = 0;
        $buildingsDestroyedByType = [];
        foreach($buildingsPerType as $buildingType => $buildings) {
            $buildingsToDestroy = $buildings * $buildingsToDestroyRatio;
            $buildingsToDestroy = round($buildingsToDestroy, 0, PHP_ROUND_HALF_EVEN);

            $totalBuildingsDestroyed += $buildingsToDestroy;

            $buildingsDestroyedByType[$buildingType] = $buildingsToDestroy;
        }

        if($totalBuildingsToDestroy != $totalBuildingsDestroyed) {
            // TODO: What should we do here?
        }

        return $buildingsDestroyedByType;
    }
}
