<?php

namespace OpenDominion\Services\Dominion;

use DB;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class QueueService
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var LandHelper */
    protected $landHelper;

    /** @var array */
    protected $explorationQueue;

    /** @var array */
    protected $constructionQueue;

    /**
     * DominionQueueService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->landHelper = app(LandHelper::class);
    }

    // Exploration

    public function getExplorationQueue(Dominion $dominion)
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

    public function getExplorationQueueTotal(Dominion $dominion)
    {
        $total = 0;
        $explorationQueue = $this->getExplorationQueue($dominion);

        foreach ($explorationQueue as $landType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    public function getExplorationQueueTotalByLand(Dominion $dominion, $land)
    {
        return array_sum($this->getExplorationQueue($dominion)[$land]);
    }

    // Construction

    public function getConstructionQueue(Dominion $dominion)
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

        $this->constructionQueue = $constructionQueue;
        return $constructionQueue;
    }

    public function getConstructionQueueTotal(Dominion $dominion)
    {
        $total = 0;
        $constructionQueue = $this->getConstructionQueue($dominion);

        foreach ($constructionQueue as $buildingType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    public function getConstructionQueueTotalByBuilding(Dominion $dominion, $building)
    {
        return array_sum($this->getConstructionQueue($dominion)[$building]);
    }

    // Military

    public function getMilitaryTrainingQueue(Dominion $dominion)
    {
        //
    }

    public function getMilitaryReturningQueue(Dominion $dominion)
    {
        //
    }
}
