<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

class LandCalculator extends AbstractDominionCalculator
{
    use DominionAwareTrait;

    /** @var LandHelper */
    protected $landHelper;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    public function __construct(Dominion $dominion)
    {
        parent::__construct($dominion);

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

        foreach (array_keys($this->landHelper->getLandTypes()) as $landType) {
            $totalLand += $this->dominion->{'land_' . $landType};
        }

        return $totalLand;
    }

    /**
     * Return's the Dominion's total acres of barren land.
     *
     * @return int
     */
    public function getTotalBarrenLand()
    {
        return ($this->getTotalLand() - $this->buildingCalculator->getTotalBuildings());
    }

    public function getBarrenLandByLandType()
    {
        return [];
    }
}
