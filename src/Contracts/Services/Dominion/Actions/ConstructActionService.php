<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use Exception;
use OpenDominion\Models\Dominion;
use RuntimeException;

interface ConstructActionService
{
    /**
     * Does a construction action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Exception
     * @throws RuntimeException
     */
    public function construct(Dominion $dominion, array $data);
}
