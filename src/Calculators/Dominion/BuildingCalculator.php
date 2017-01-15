<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class BuildingCalculator extends AbstractDominionCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        parent::init($dominion);

        $this->buildingHelper = app()->make(BuildingHelper::class);
        $this->landCalculator = app()->make(LandCalculator::class)->setDominion($dominion);
    }

    /**
     * Returns the Dominion' total number of buildings.
     *
     * @return int
     */
    public function getTotalBuildings()
    {
        $totalBuildings = 0;

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $totalBuildings += $this->dominion->{'building_' . $buildingType};
        }

        return $totalBuildings;
    }

    /**
     * Returns the Dominion's construction platinum cost per building.
     *
     * @return int
     */
    public function getConstructionPlatinumCost()
    {
        $platinum = 0;
        $totalBuildings = $this->getTotalBuildings();
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalBuildings >= 1250) {
            $platinum += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $platinum += $totalLand;
        }

        $platinum -= 250;
        $platinum *= 1.53;
        $platinum += 850;

        return (int)round($platinum);
    }

    /**
     * Returns the Dominion's construction lumber cost per building.
     *
     * @return int
     */
    public function getConstructionLumberCost()
    {
        $lumber = 0;
        $totalBuildings = $this->getTotalBuildings();
        $totalLand = $this->landCalculator->getTotalLand();

        if ($totalBuildings >= 1250) {
            $lumber += max(
                ($totalLand * 0.75),
                $totalBuildings
            );
        } else {
            $lumber += $totalLand;
        }

        $lumber -= 250;
        $lumber *= 0.6;
        $lumber += 88;

        return (int)round($lumber);
    }

    /**
     * Returns the maximum number of building a Dominion can construct.
     *
     * @return int
     */
    public function getConstructionMaxAfford()
    {
        return (int)round(min(
            floor($this->dominion->resource_platinum / $this->getConstructionPlatinumCost()),
            floor($this->dominion->resource_lumber / $this->getConstructionLumberCost())
        ));
    }
}
