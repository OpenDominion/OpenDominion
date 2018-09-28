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
            $buildingsForType = $dominion->{$resourceName};
            $totalBuildingsForLandType += $buildingsForType;

            $buildingsInQueueForType = $this->queueService->getConstructionQueueTotalByResource($dominion, $resourceName);
            $buildingsPerType[$buildingType] = array(
                'constructedBuildings' => $buildingsForType,
                'buildingsInQueue' => $buildingsInQueueForType);
        }

        if($totalBuildingsForLandType <= 0) {
            // :/
            dd(['$totalBuildingsToDestroy' => $totalBuildingsToDestroy, 'landType' => $landType ]);
        }

        $buildingsToDestroyRatio = $totalBuildingsToDestroy / $totalBuildingsForLandType;
        $totalBuildingsDestroyed = 0;
        $buildingsDestroyedByType = [];
        foreach($buildingsPerType as $buildingType => $buildings) {
            $constructedBuildings = $buildings['constructedBuildings'];
            $buildingsInQueue = $buildings['buildingsInQueue'];

            $totalBuildings = $constructedBuildings + $buildingsInQueue;
            $buildingsToDestroy = $totalBuildings * $buildingsToDestroyRatio;
            $buildingsToDestroy = round($buildingsToDestroy, 0, PHP_ROUND_HALF_EVEN);

            if($buildingsToDestroy <= 0) {
                continue;
            }

            $buildingsInQueueToDestroy = 0;
            // take buildings in queue first
            if($buildingsInQueue <= $buildingsToDestroy) {
                $buildingsInQueueToDestroy = $buildingsInQueue;
            }
            else {
                $buildingsInQueueToDestroy = $buildingsToDestroy;
            }

            $constructedBuildingsToDestroy = $buildingsToDestroy - $buildingsInQueueToDestroy;

            $totalBuildingsDestroyed += $buildingsToDestroy;

            $buildingsDestroyedByType[$buildingType] = array(
                'builtBuildingsToDestroy' => $constructedBuildingsToDestroy,
                'buildingsInQueueToRemove' => $buildingsInQueueToDestroy);
        }

        if($totalBuildingsToDestroy != $totalBuildingsDestroyed) {
            // TODO: What should we do here?
            // Maybe just take the missing acres from the largest building type?
        }

        return $buildingsDestroyedByType;
    }
}
