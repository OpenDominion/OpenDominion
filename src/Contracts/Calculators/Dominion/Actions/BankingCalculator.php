<?php

namespace OpenDominion\Contracts\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface BankingCalculator
{
    /**
     * Returns resources and prices for exchanging.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getResources(Dominion $dominion): array;

}
