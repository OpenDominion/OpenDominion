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
     * Returns the Dominion' total number of constructed buildings.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTotalBuildings(Dominion $dominion)
    {
        $totalBuildings = 0;

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $totalBuildings += $dominion->{'building_' . $buildingType};
        }

        return $totalBuildings;
    }

    // todo: building under construction?
}
