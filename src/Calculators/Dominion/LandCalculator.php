<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Contracts\Calculators\Dominion\LandCalculator as LandCalculatorContract;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\DominionQueueService;

class LandCalculator implements LandCalculatorContract
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandHelper */
    protected $landHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    /** @var DominionQueueService */
    protected $dominionQueueService;

    /**
     * LandCalculator constructor.
     *
     * @param BuildingCalculator $buildingCalculator
     * @param BuildingHelper $buildingHelper
     * @param LandHelper $landHelper
     * @param DominionQueueService $dominionQueueService
     */
    public function __construct(
        BuildingCalculator $buildingCalculator,
        BuildingHelper $buildingHelper,
        LandHelper $landHelper,
        DominionQueueService $dominionQueueService
    ) {
        $this->buildingCalculator = $buildingCalculator;
        $this->buildingHelper = $buildingHelper;
        $this->landHelper = $landHelper;
        $this->dominionQueueService = $dominionQueueService;
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
            - $this->dominionQueueService->getConstructionQueueTotal($dominion)
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
                $barrenLand -= $this->dominionQueueService->getConstructionQueueTotalByBuilding($dominion, $buildingType);
            }

            $return[$landType] = $barrenLand;
        }

        return $return;
    }
}
