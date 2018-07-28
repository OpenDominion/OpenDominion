<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class ReturningQueueService
{
    /** @var array */
    protected $returningQueue;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * ReturningQueueService constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(UnitHelper $unitHelper)
    {
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the returning queue of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getQueue(Dominion $dominion): array
    {
        if ($this->returningQueue) {
            return $this->returningQueue;
        }

        $rows = DB::table('queue_returning')
            ->where('dominion_id', $dominion->id)
            ->get(['unit_type', 'amount', 'hours']);

        $returningQueue = array_fill_keys($this->unitHelper->getUnitTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $returningQueue[$row->unit_type][$row->hours - 1] = (int)$row->amount;
        }

        return $this->returningQueue = $returningQueue;
    }

    /**
     * Returns the total number of units returning of a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(Dominion $dominion): int
    {
        $total = 0;
        $returningQueue = $this->getQueue($dominion);

        foreach ($returningQueue as $unitType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Returns the total amount of a specific unit returning by a dominion.
     *
     * @param Dominion $dominion
     * @param string $unitType
     * @return int
     */
    public function getQueueTotalByUnitType(Dominion $dominion, string $unitType): int
    {
        $queue = $this->getQueue($dominion);

        if (!isset($queue[$unitType])) {
            return 0;
        }

        return array_sum($queue[$unitType]);
    }
}
