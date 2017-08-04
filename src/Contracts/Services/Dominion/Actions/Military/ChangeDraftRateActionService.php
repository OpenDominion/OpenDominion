<?php

namespace OpenDominion\Contracts\Services\Dominion\Actions\Military;

use OpenDominion\Models\Dominion;
use RuntimeException;

interface ChangeDraftRateActionService
{
    /**
     * @param Dominion $dominion
     * @param int $draftRate
     * @return array
     * @throws RuntimeException
     */
    public function changeDraftRate(Dominion $dominion, $draftRate);
}
