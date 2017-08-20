<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Contracts\Services\Dominion\Queue\TrainingQueueService as TrainingQueueServiceContract;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class TrainingQueueService implements TrainingQueueServiceContract
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

    public function getQueue(Dominion $dominion)
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

    public function getQueueTotal(Dominion $dominion)
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

    public function getQueueTotalByUnitType(Dominion $dominion, string $unitType)
    {
        return array_sum($this->getQueue($dominion)[$unitType]);
    }
}
