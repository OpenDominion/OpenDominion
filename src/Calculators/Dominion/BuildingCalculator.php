<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Traits\DominionAwareTrait;

class BuildingCalculator
{
    use DominionAwareTrait;

    /** @var BuildingHelper */
    protected $buildingHelper;

    public function __construct(BuildingHelper $buildingHelper)
    {
        $this->buildingHelper = $buildingHelper;
    }

    /**
     * Returns the Dominion' total number of buildings.
     *
     * @return int
     */
    public function getTotalBuildings()
    {
        $totalBuildings = 0;

        foreach (array_keys($this->buildingHelper->getBuildingTypes()) as $buildingType) {
            $totalBuildings += $this->dominion->{'building_' . $buildingType};
        }

        return $totalBuildings;
    }
}
