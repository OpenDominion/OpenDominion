<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions\Military;

use Exception;
use OpenDominion\Models\Dominion;
use RuntimeException;

interface TrainActionService
{
    /**
     * Does a military train action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws RuntimeException
     * @throws Exception
     */
    public function train(Dominion $dominion, array $data);
}
