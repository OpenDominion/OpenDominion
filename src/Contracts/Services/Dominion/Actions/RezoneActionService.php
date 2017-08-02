<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use Exception;
use OpenDominion\Models\Dominion;
use RuntimeException;

interface RezoneActionService
{
    /**
     * Does a rezone action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $remove Land to remove
     * @param array $add Land to add.
     * @return array
     * @throws Exception
     * @throws RuntimeException
     */
    public function rezone(Dominion $dominion, array $remove, array $add);
}
