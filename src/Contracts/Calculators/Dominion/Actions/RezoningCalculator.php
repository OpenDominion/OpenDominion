<?php

namespace OpenDominion\Contracts\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface RezoningCalculator
{
    public function getPlatinumCost(Dominion $dominion);

    public function getMaxAfford(Dominion $dominion);
}
