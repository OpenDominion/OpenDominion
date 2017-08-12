<?php

namespace OpenDominion\Contracts\Calculators\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface BankingCalculator
{
    /**
     * Returns resources and prices for exchanging.
     *
     * @return array
     */
    public function getResources(Dominion $dominion): array;

}
