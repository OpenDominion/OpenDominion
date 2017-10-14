<?php

namespace OpenDominion\Services\Dominion\Actions\Military;

use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ChangeDraftRateActionService
{
    use DominionGuardsTrait;

    /**
     * Does a military change draft rate action for a Dominion.
     *
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws RuntimeException
     */
    public function changeDraftRate(Dominion $dominion, int $draftRate): array
    {
        $this->guardLockedDominion($dominion);

        $draftRate = (int)$draftRate;

        if (($draftRate < 0) || ($draftRate > 90)) {
            throw new RuntimeException('Draft rate not changed due to bad input.');
        }

        $dominion->draft_rate = $draftRate;
        $dominion->save();

        return [
            'message' => sprintf('Draft rate changed to %d%%.', $draftRate),
            'data' => [
                'draftRate' => $draftRate,
            ],
        ];
    }
}
