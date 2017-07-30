<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use Exception;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;

interface ConstructActionService
{
    /**
     * Does a construction action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     * @throws Exception
     * @throws NotEnoughResourcesException
     */
    public function construct(Dominion $dominion, array $data);
}
