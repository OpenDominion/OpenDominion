<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Models\Dominion;

interface DestroyActionService
{
    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     */
    public function destroy(Dominion $dominion, array $data);
}
