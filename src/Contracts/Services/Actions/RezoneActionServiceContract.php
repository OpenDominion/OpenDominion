<?php

namespace OpenDominion\Contracts\Services\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;

interface RezoneActionServiceContract
{
    /**
     * Does a rezone action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $remove Land to remove
     * @param array $add Land to add.
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     * @throws NotEnoughResourcesException
     */
    public function rezone(Dominion $dominion, array $remove, array $add);
}
