<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\Queue\ConstructionQueueService;

class LandCalculator
{
    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ConstructionQueueService */
    protected $constructionQueueService;

    /** @var LandHelper */
    protected $landHelper;

    /**
     * LandCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param BuildingHelper $buildingHelper
     * @param ConstructionQueueService $constructionQueueService
     * @param LandHelper $landHelper
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        BuildingHelper $buildingHelper,
        ConstructionQueueService $constructionQueueService,
        LandHelper $landHelper
    ) {
        $this->buildingCalculator = $buildingCalculator;
        $this->buildingHelper = $buildingHelper;
        $this->constructionQueueService = $constructionQueueService;
        $this->landHelper = $landHelper;
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
            - $this->constructionQueueService->getQueueTotal($dominion)
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
                $barrenLand -= $dominion->{'building_' . $buildingType};
                $barrenLand -= $this->constructionQueueService->getQueueTotalByBuilding($dominion, $buildingType);
            }

            $return[$landType] = $barrenLand;
        }

        return $return;
    }

    public function getLandLostByLandType(Dominion $dominion, float $landLossRatio): array
    {
        $targetLand = $this->getTotalLand($dominion);

        $totalLandToLose = floor($targetLand * $landLossRatio);

        $barrenLandByLandType = $this->getBarrenLandByLandType($dominion);
        $totalLandLost = 0;
        $landLostByLandType = [];
        foreach ($this->landHelper->getLandTypes() as $landType) {
            $landTypeLoss = $dominion->{'land_' . $landType} * $landLossRatio;

            $totalLandTypeLoss = round($landTypeLoss, 0, PHP_ROUND_HALF_EVEN); // bankers rounding </3
            $totalLandLost += $totalLandTypeLoss;

            $barrenLandForLandType = $barrenLandByLandType[$landType];

            $barrenLandLostForLandType = 0;
            if($barrenLandForLandType <= $totalLandTypeLoss) {
                $barrenLandLostForLandType = $barrenLandForLandType;
            } else {
                $barrenLandLostForLandType = $totalLandTypeLoss;
            }

            $buildingsToDestroy = $totalLandTypeLoss - $barrenLandLostForLandType;
            $landLostByLandType[$landType] = array(
                'landLost' => $totalLandTypeLoss,
                'barrenLandLost' => $barrenLandLostForLandType,
                'buildingsToDestroy' => $buildingsToDestroy);
        }

        if($totalLandToLose != $totalLandLost){
            // TODO: What should we do here?
            // Maybe just take the missing acres from the largest land type?
        }

        return $landLostByLandType;
    }
}
