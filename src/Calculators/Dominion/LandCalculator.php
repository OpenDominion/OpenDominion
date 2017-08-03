<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator as LandCalculatorContract;
use OpenDominion\Contracts\Services\Dominion\Queue\ConstructionQueueService;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class LandCalculator implements LandCalculatorContract
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
     * {@inheritdoc}
     */
    public function getTotalLand(Dominion $dominion)
    {
        $totalLand = 0;

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $totalLand += $dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalBarrenLand(Dominion $dominion)
    {
        return (
            $this->getTotalLand($dominion)
            - $this->buildingCalculator->getTotalBuildings($dominion)
            - $this->constructionQueueService->getQueueTotal($dominion)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalBarrenLandByLandType(Dominion $dominion, $landType)
    {
        return $this->getBarrenLand($dominion)[$landType];
    }

    /**
     * {@inheritdoc}
     */
    public function getBarrenLand(Dominion $dominion)
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
}
