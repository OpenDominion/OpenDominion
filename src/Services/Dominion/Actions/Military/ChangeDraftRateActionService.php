<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Contracts\Services\Dominion\Actions\Military\ChangeDraftRateActionService as ChangeDraftRateActionServiceContract;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

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
            throw new RuntimeException('Draft rate not changed due to bad input.');
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save();

        return compact('draftRate');
    }
}
