<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class LandCalculator extends AbstractDominionCalculator
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandHelper */
    protected $landHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

        $this->buildingHelper = app()->make(BuildingHelper::class);
        $this->landHelper = app()->make(LandHelper::class);
        $this->buildingCalculator = app()->make(BuildingCalculator::class, [$dominion]);
    }

    /**
     * Returns the Dominion's total acres of land.
     *
     * @return int
     */
    public function getTotalLand()
    {
        $totalLand = 0;

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $totalLand += $this->dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * Returns the Dominion's total acres of barren land.
     *
     * @return int
     */
    public function getTotalBarrenLand()
    {
        // todo: construction queue

        return ($this->getTotalLand() - $this->buildingCalculator->getTotalBuildings());
    }

    /**
     * Returns the Dominion's barren land by land type.
     *
     * @return int[]
     */
    public function getBarrenLandByLandType()
    {
        $buildingTypesbyLandType = $this->buildingHelper->getBuildingTypesByLandType($this->dominion->race);

        $return = [];

        foreach ($buildingTypesbyLandType as $landType => $buildingTypes) {
            $barrenLand = $this->dominion->{'land_' . $landType};

            foreach ($buildingTypes as $buildingType) {
                $barrenLand -= $this->dominion->{'building_' . $buildingType};
            }

            // todo: construction queue

            $return[$landType] = $barrenLand;
        }

        return $return;
    }
}
