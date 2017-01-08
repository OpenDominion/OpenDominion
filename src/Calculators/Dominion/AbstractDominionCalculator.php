<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Models\Dominion;

abstract class AbstractDominionCalculator
{
    /** @var Dominion */
    protected $dominion;

    /**
     * @param Dominion $dominion
     */
    public function setDominion(Dominion $dominion)
    {
        $this->dominion = $dominion;
    }
}
