<?php

namespace OpenDominion\Services\Actions;

use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Models\Dominion;

class MilitaryActionService
{
    /**
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws BadInputException
     */
    public function changeDraftRate(Dominion $dominion, $draftRate)
    {
        $draftRate = (int)$draftRate;

        if (($draftRate < 0) || ($draftRate > 100)) {
            throw new BadInputException;
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save();

        return compact('draftRate');
    }
}
