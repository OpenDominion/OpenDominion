<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions\Military;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Models\Dominion;

interface ChangeDraftRateActionService
{
    /**
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     */
    public function changeDraftRate(Dominion $dominion, $draftRate);
}
