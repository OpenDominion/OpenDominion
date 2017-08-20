<?php

namespace OpenDominion\Contracts\Services\Dominion\Queue;

use OpenDominion\Models\Dominion;

interface TrainingQueueService
{
    public function getQueue(Dominion $dominion);

    public function getQueueTotal(Dominion $dominion);

    public function getQueueTotalByUnitType(Dominion $dominion, string $unitType);
}
