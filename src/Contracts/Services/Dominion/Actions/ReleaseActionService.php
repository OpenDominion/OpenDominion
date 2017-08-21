<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use OpenDominion\Models\Dominion;
use RuntimeException;

interface ReleaseActionService
{
    /**
     * Does a release troops action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws RuntimeException
     */
    public function release(Dominion $dominion, array $data);
}
