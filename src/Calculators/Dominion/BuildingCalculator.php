<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class BuildingCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var QueueService */
    protected $queueService;

    /**
     * BuildingCalculator constructor.
     *
     * @param BuildingHelper $buildingHelper
     * @param QueueService $queueService
     */
    public function __construct(BuildingHelper $buildingHelper, QueueService $queueService)
    {
        $this->buildingHelper = $buildingHelper;
        $this->queueService = $queueService;
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
        if($totalBuildingsToDestroy <= 0) {
            return [];
        }

        // TODO: Check the queue for inc buildings as well
        $buildingTypesForLandType = $this->buildingHelper->getBuildingTypesByRace($dominion->race)[$landType];

        $buildingsPerType = [];

        $totalBuildingsForLandType = 0;

        foreach($buildingTypesForLandType as $buildingType) {
            $resourceName = "building_{$buildingType}";
            $buildingsForType = $dominion->$resourceName;
            $totalBuildingsForLandType += $buildingsForType;

            $buildingsInQueueForType = $this->queueService->getConstructionQueueTotalByResource($dominion, $resourceName);
            $totalBuildingsForLandType += $buildingsInQueueForType;

            $buildingsPerType[$buildingType] = [
                'constructedBuildings' => $buildingsForType,
                'buildingsInQueue' => $buildingsInQueueForType];
        }

        uasort($buildingsPerType, function ($item1, $item2) {
            $item1Total = $item1['constructedBuildings'] + $item1['buildingsInQueue'];
            $item2Total = $item2['constructedBuildings'] + $item2['buildingsInQueue'];

            return $item2Total <=> $item1Total;
        });

        $buildingsToDestroyRatio = $totalBuildingsToDestroy / $totalBuildingsForLandType;

        $buildingsLeftToDestroy = $totalBuildingsToDestroy;
        $initialTotalBuildingsDestroyed = 0;
        $buildingsToDestroyByType = [];
        foreach($buildingsPerType as $buildingType => $buildings) {
            if($buildingsLeftToDestroy == 0) {
                break;
            }

            $constructedBuildings = $buildings['constructedBuildings'];
            $buildingsInQueue = $buildings['buildingsInQueue'];

            $totalBuildings = $constructedBuildings + $buildingsInQueue;
            $buildingsToDestroy = (int)ceil($totalBuildings * $buildingsToDestroyRatio);

            if($buildingsToDestroy <= 0) {
                continue;
            }

            if($buildingsToDestroy > $buildingsLeftToDestroy) {
                $buildingsToDestroy = $buildingsLeftToDestroy;
            }

            $buildingsToDestroyByType[$buildingType] = $buildingsToDestroy;

            $initialTotalBuildingsDestroyed += $buildingsToDestroy;
            $buildingsLeftToDestroy -= $buildingsToDestroy;
        }

        if($initialTotalBuildingsDestroyed != $totalBuildingsToDestroy) {
            // TODO: Remove? Log?
        }

        $actualTotalBuildingsDestroyed = 0;
        $buildingsDestroyedByType = [];
        foreach($buildingsToDestroyByType as $buildingType => $buildingsToDestroy) {
            $buildings = $buildingsPerType[$buildingType];
            $constructedBuildings = $buildings['constructedBuildings'];
            $buildingsInQueue = $buildings['buildingsInQueue'];

            $buildingsInQueueToDestroy = 0;
            // take buildings in queue first
            if($buildingsInQueue <= $buildingsToDestroy) {
                $buildingsInQueueToDestroy = $buildingsInQueue;
            }
            else {
                $buildingsInQueueToDestroy = $buildingsToDestroy;
            }

            $constructedBuildingsToDestroy = $buildingsToDestroy - $buildingsInQueueToDestroy;

            $actualTotalBuildingsDestroyed += $buildingsToDestroy;

            $buildingsDestroyedByType[$buildingType] = [
                'builtBuildingsToDestroy' => $constructedBuildingsToDestroy,
                'buildingsInQueueToRemove' => $buildingsInQueueToDestroy];
        }

        return $buildingsDestroyedByType;
    }
}
