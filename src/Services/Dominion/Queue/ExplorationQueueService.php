<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Contracts\Services\Dominion\Queue\ExplorationQueueService as ExplorationQueueServiceContract;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class ExplorationQueueService implements ExplorationQueueServiceContract
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
     * {@inheritdoc}
     */
    public function getQueue(Dominion $dominion)
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

        $this->explorationQueue = $explorationQueue;
        return $explorationQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueTotal(Dominion $dominion)
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
     * {@inheritdoc}
     */
    public function getQueueTotalByLand(Dominion $dominion, $land)
    {
        return array_sum($this->getQueue($dominion)[$land]);
    }
}
