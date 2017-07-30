<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use Exception;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;

interface ExploreActionService
{
    /**
     * Does an explore action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws DominionLockedException
     * @throws Exception
     * @throws BadIn
     * putException
     * @throws NotEnoughResourcesException
     */
    public function explore(Dominion $dominion, array $data);
}
