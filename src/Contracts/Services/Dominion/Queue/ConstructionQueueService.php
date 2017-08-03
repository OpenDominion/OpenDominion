<?php

namespace OpenDominion\Contracts\Services\Dominion\Queue;

use OpenDominion\Models\Dominion;

interface ConstructionQueueService
{
    public function getQueue(Dominion $dominion);

    public function getQueueTotal(Dominion $dominion);

    public function getQueueTotalByBuilding(Dominion $dominion, $building);
}
