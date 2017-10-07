<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class ExplorationQueueService
{
    /** @var LandHelper */
    protected $landHelper;

    /** @var array */
    protected $explorationQueue;

    /**
     * ExplorationQueueService constructor.
     *
     * @param LandHelper $landHelper
     */
    public function __construct(LandHelper $landHelper)
    {
        $this->landHelper = $landHelper;
    }

    /**
     * Returns the exploration queue of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getQueue(Dominion $dominion): array
    {
        if ($this->explorationQueue) {
            return $this->explorationQueue;
        }

        $rows = DB::table('queue_exploration')
            ->where('dominion_id', $dominion->id)
            ->get(['land_type', 'amount', 'hours']);

        $explorationQueue = array_fill_keys($this->landHelper->getLandTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $explorationQueue[$row->land_type][$row->hours - 1] = (int)$row->amount;
        }

        return $this->explorationQueue = $explorationQueue;
    }

    /**
     * Returns the total number of land being explored of a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(Dominion $dominion): int
    {
        $total = 0;
        $explorationQueue = $this->getQueue($dominion);

        foreach ($explorationQueue as $landType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Returns the total number of a specific land being explored by a
     * dominion.
     *
     * @param Dominion $dominion
     * @param string $landType
     * @return int
     */
    public function getQueueTotalByLand(Dominion $dominion, string $landType): int
    {
        return array_sum($this->getQueue($dominion)[$landType]);
    }
}
