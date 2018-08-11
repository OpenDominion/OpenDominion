<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Models\Dominion;

class ConstructionQueueService
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
     * Returns the construction queue of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getQueue(Dominion $dominion): array
    {
        if ($this->constructionQueue && array_key_exists($dominion->id, $this->constructionQueue)) {
            return $this->constructionQueue[$dominion->id];
        }

        $rows = DB::table('queue_construction')
            ->where('dominion_id', $dominion->id)
            ->get(['building', 'amount', 'hours']);

        $constructionQueue = array_fill_keys($this->buildingHelper->getBuildingTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $constructionQueue[$row->building][$row->hours - 1] = (int)$row->amount;
        }
        
        return $this->constructionQueue[$dominion->id] = $constructionQueue;
    }

    /**
     * Returns the total number of buildings being constructed of a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(Dominion $dominion): int
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
     * Returns the total number of a specific building being constructed by a
     * dominion.
     *
     * @param Dominion $dominion
     * @param string $buildingType
     * @return int
     */
    public function getQueueTotalByBuilding(Dominion $dominion, string $buildingType): int
    {
        return array_sum($this->getQueue($dominion)[$buildingType]);
    }
}
