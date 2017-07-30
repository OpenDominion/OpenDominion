<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService as ChangeDraftRateActionServiceContract;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class ChangeDraftRateActionService implements ChangeDraftRateActionServiceContract
{
    use DominionGuardsTrait;

    /**
     * {@inheritdoc}
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
