<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class ChangeDraftRateActionService
{
    use DominionGuardsTrait;

    /**
     * Does a military change draft rate action for a Dominion.
     *
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws GameException
     */
    public function changeDraftRate(Dominion $dominion, int $draftRate): array
    {
        $this->guardLockedDominion($dominion);

        if (($draftRate < 0) || ($draftRate > 90)) {
            throw new GameException('Draft rate not changed due to bad input.');
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_CHANGE_DRAFT_RATE]);

        return [
            'message' => sprintf('Draft rate changed to %d%%.', $draftRate),
            'data' => [
                'draftRate' => $draftRate,
            ],
        ];
    }
}
