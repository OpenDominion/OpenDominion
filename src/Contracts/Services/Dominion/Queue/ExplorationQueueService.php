<?php

namespace OpenDominion\Contracts\Services\Dominion\Queue;

use OpenDominion\Models\Dominion;

interface ExplorationQueueService
{
    public function getQueue(Dominion $dominion);

    public function getQueueTotal(Dominion $dominion);

    public function getQueueTotalByLand(Dominion $dominion, $land);
}
