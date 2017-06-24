<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Contracts\Calculators\Dominion\BuildingCalculator as BuildingCalculatorContract;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class BuildingCalculator implements BuildingCalculatorContract
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
     * {@inheritdoc}
     */
    public function getTotalBuildings(Dominion $dominion)
    {
        $totalBuildings = 0;

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $totalBuildings += $dominion->{'building_' . $buildingType};
        }

        return $totalBuildings;
    }
}
