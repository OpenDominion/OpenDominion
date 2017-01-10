<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\LandHelper;
use OpenDominion\Traits\DominionAwareTrait;

class LandCalculator
{
    use DominionAwareTrait;

    /** @var LandHelper */
    protected $landHelper;

    public function __construct(LandHelper $landHelper)
    {
        $this->landHelper = $landHelper;
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

    public function getTotalBarrenLand()
    {
        return 0;
    }

    public function getBarrenLandByLandType()
    {
        return [];
    }
}
