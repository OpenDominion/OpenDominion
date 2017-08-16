<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use RuntimeException;

interface BankActionService
{
    /**
     * Does a bank action for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $source
     * @param string $target
     * @param int $amount
     * @throws RuntimeException
     */
    public function exchange(Dominion $dominion, string $source, string $target, int $amount): void;
}
