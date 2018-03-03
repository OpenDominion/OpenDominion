<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class TrainingQueueService
{
    /** @var array */
    protected $trainingQueue;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * TrainingQueueService constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(UnitHelper $unitHelper)
    {
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the training queue of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getQueue(Dominion $dominion): array
    {
        if ($this->trainingQueue) {
            return $this->trainingQueue;
        }

        $rows = DB::table('queue_training')
            ->where('dominion_id', $dominion->id)
            ->get(['unit_type', 'amount', 'hours']);

        $trainingQueue = array_fill_keys($this->unitHelper->getUnitTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $trainingQueue[$row->unit_type][$row->hours - 1] = (int)$row->amount;
        }

        return $this->trainingQueue = $trainingQueue;
    }

    /**
     * Returns the total number of units being trained of a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(Dominion $dominion): int
    {
        $total = 0;
        $trainingQueue = $this->getQueue($dominion);

        foreach ($trainingQueue as $unitType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Returns the total number of a specific unit being trained by a dominion.
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
