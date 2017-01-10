<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Traits\DominionAwareTrait;

class LandCalculator
{
    use DominionAwareTrait;

    public function getTotalLand()
    {
        return 0;
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
