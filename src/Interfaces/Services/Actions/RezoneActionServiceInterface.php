<?php

namespace OpenDominion\Interfaces\Services\Actions;

use OpenDominion\Models\Dominion;

interface RezoneActionServiceInterface
{
    /**
     * Does a rezone action for a Dominion.
     *
     * @param \OpenDominion\Models\Dominion $dominion
     *   The dominion to rezone.
     * @param array $remove
     *   The land to remove.
     * @param array $add
     *   The land to add.
     *
     * @return int
     *   The amount of platinum spent.
     * @throws \OpenDominion\Exceptions\BadInputException
     * @throws \OpenDominion\Exceptions\NotEnoughResourcesException
     * @throws \OpenDominion\Exceptions\DominionLockedException
     */
    public function rezone(Dominion $dominion, array $remove, array $add);
}
