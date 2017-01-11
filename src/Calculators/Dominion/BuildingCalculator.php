<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

class BuildingCalculator extends AbstractDominionCalculator
{
    use DominionAwareTrait;

    /** @var BuildingHelper */
    protected $buildingHelper;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->buildingHelper = app()->make(BuildingHelper::class);
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
