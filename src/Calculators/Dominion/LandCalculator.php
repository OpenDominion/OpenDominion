<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class LandCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var QueueService */
    protected $queueService;

    /** @var LandHelper */
    protected $landHelper;

    /**
     * LandCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param BuildingHelper $buildingHelper
     * @param LandHelper $landHelper
     * @param QueueService $queueService
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        BuildingHelper $buildingHelper,
        LandHelper $landHelper,
        QueueService $queueService
    ) {
        $this->buildingCalculator = $buildingCalculator;
        $this->buildingHelper = $buildingHelper;
        $this->landHelper = $landHelper;
        $this->queueService = $queueService;
    }

    /**
     * Returns the Dominion's total acres of land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalLand(Dominion $dominion): int
    {
        $totalLand = 0;

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $totalLand += $dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * Returns the Dominion's total acres of land including incoming.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalLandIncoming(Dominion $dominion): int
    {
        return(
            $this->getTotalLand($dominion)
            + $this->queueService->getExplorationQueueTotal($dominion)
            + $this->queueService->getInvasionQueueTotalByPrefix($dominion, 'land_')
        );

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $totalLand += $dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * Returns the Dominion's total acres of barren land.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalBarrenLand(Dominion $dominion): int
    {
        return (
            $this->getTotalLand($dominion)
            - $this->buildingCalculator->getTotalBuildings($dominion)
            - $this->queueService->getConstructionQueueTotal($dominion)
        );
    }

    /**
     * Returns the Dominion's total barren land by land type.
     *
     * @param Dominion $dominion
     * @param string $landType
     * @return int
     */
    public function getTotalBarrenLandByLandType(Dominion $dominion, $landType): int
    {
        return $this->getBarrenLandByLandType($dominion)[$landType];
    }

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getBarrenLandByLandType(Dominion $dominion): array
    {
        $buildingTypesbyLandType = $this->buildingHelper->getBuildingTypesByRace($dominion->race);

        $return = [];

        foreach ($buildingTypesbyLandType as $landType => $buildingTypes) {
            $barrenLand = $dominion->{'land_' . $landType};

            foreach ($buildingTypes as $buildingType) {
                $barrenLand -= $dominion->{"building_{$buildingType}"};
                $barrenLand -= $this->queueService->getConstructionQueueTotalByResource($dominion, "building_{$buildingType}");
            }

            $return[$landType] = $barrenLand;
        }

        return $return;
    }

    public function getLandByLandType(Dominion $dominion): array
    {
        $return = [];
        foreach ($this->landHelper->getLandTypes() as $landType) {
            $return[$landType] = $dominion->{"land_{$landType}"};
        }

        return $return;
    }

    public function getLandLostByLandType(Dominion $dominion, float $landLossRatio): array
    {
        $targetLand = $this->getTotalLand($dominion);
        $totalLandToLose = (int)floor($targetLand * $landLossRatio);
        $barrenLandByLandType = $this->getBarrenLandByLandType($dominion);
        $landPerType = $this->getLandByLandType($dominion);

        arsort($landPerType);

        $landLeftToLose = $totalLandToLose;
//        $totalLandLost = 0;
        $landLostByLandType = [];

        foreach ($landPerType as $landType => $totalLandForType) {
            if ($landLeftToLose === 0) {
                break;
            }

            $landTypeLoss = ($totalLandForType * $landLossRatio);

            $totalLandTypeLoss = (int)ceil($landTypeLoss);

            if ($totalLandTypeLoss === 0) {
                continue;
            }

            if ($totalLandTypeLoss > $landLeftToLose) {
                $totalLandTypeLoss = $landLeftToLose;
            }

//            $totalLandLost += $totalLandTypeLoss;
            $barrenLandForLandType = $barrenLandByLandType[$landType];

            if ($barrenLandForLandType <= $totalLandTypeLoss) {
                $barrenLandLostForLandType = $barrenLandForLandType;
            } else {
                $barrenLandLostForLandType = $totalLandTypeLoss;
            }

            $buildingsToDestroy = $totalLandTypeLoss - $barrenLandLostForLandType;
            $landLostByLandType[$landType] = [
                'landLost' => $totalLandTypeLoss,
                'barrenLandLost' => $barrenLandLostForLandType,
                'buildingsToDestroy' => $buildingsToDestroy
            ];

            $landLeftToLose -= $totalLandTypeLoss;
        }

        return $landLostByLandType;
    }
}
