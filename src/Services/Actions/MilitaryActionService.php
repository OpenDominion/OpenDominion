<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class MilitaryActionService
{
    use DominionGuardsTrait;

    /**
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws DominionLockedException
     * @throws BadInputException
     */
    public function changeDraftRate(Dominion $dominion, $draftRate)
    {
        $this->guardLockedDominion($dominion);

        $draftRate = (int)$draftRate;

        if (($draftRate < 0) || ($draftRate > 100)) {
            throw new BadInputException;
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save();

        return compact('draftRate');
    }
}
