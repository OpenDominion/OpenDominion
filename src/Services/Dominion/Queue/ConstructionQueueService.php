<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Contracts\Services\Dominion\Queue\ConstructionQueueService as ConstructionQueueServiceContract;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class ConstructionQueueService implements ConstructionQueueServiceContract
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var array */
    protected $constructionQueue;

    /**
     * ConstructionQueueService constructor.
     *
     * @param BuildingHelper $buildingHelper
     */
    public function __construct(BuildingHelper $buildingHelper)
    {
        $this->buildingHelper = $buildingHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue(Dominion $dominion)
    {
        if ($this->constructionQueue) {
            return $this->constructionQueue;
        }

        $rows = DB::table('queue_construction')
            ->where('dominion_id', $dominion->id)
            ->get(['building', 'amount', 'hours']);

        $constructionQueue = array_fill_keys($this->buildingHelper->getBuildingTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $constructionQueue[$row->building][$row->hours - 1] = (int)$row->amount;
        }

        return $this->constructionQueue = $constructionQueue;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueTotal(Dominion $dominion)
    {
        $total = 0;
        $constructionQueue = $this->getQueue($dominion);

        foreach ($constructionQueue as $buildingType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueTotalByBuilding(Dominion $dominion, $building)
    {
        return array_sum($this->getQueue($dominion)[$building]);
    }
}
