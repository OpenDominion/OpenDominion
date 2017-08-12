<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;

interface BankActionService
{
    /**
     * Does a bank action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $source
     * @param string $target
     * @param int $amount
     */
    public function exchange(Dominion $dominion, $source, $target, $amount);
}
