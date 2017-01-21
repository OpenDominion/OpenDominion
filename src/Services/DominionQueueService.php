<?php

namespace OpenDominion\Services;

use DB;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Interfaces\DominionInitializableInterface;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

class DominionQueueService implements DominionInitializableInterface
{
    use DominionAwareTrait;

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
        $this->buildingHelper = app()->make(BuildingHelper::class);
        $this->landHelper = app()->make(LandHelper::class);
    }

    /**
     * {@inheritDoc}
     */
    public function init(Dominion $dominion)
    {
        $this->setDominion($dominion);
        return $this;
    }

    // Exploration

    public function getExplorationQueue()
    {
        if ($this->explorationQueue) {
            return $this->explorationQueue;
        }

        $rows = DB::table('queue_exploration')
            ->where('dominion_id', $this->dominion->id)
            ->get(['land_type', 'amount', 'hours']);

        $explorationQueue = array_fill_keys($this->landHelper->getLandTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $explorationQueue[$row->land_type][$row->hours - 1] = (int)$row->amount;
        }

        $this->explorationQueue = $explorationQueue;
        return $explorationQueue;
    }

    public function getExplorationQueueTotal()
    {
        $total = 0;
        $explorationQueue = $this->getExplorationQueue();

        foreach ($explorationQueue as $landType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    public function getExplorationQueueTotalByLand($land)
    {
        return array_sum($this->getExplorationQueue()[$land]);
    }

    // Construction

    public function getConstructionQueue()
    {
        if ($this->constructionQueue) {
            return $this->constructionQueue;
        }

        $rows = DB::table('queue_construction')
            ->where('dominion_id', $this->dominion->id)
            ->get(['building', 'amount', 'hours']);

        $constructionQueue = array_fill_keys($this->buildingHelper->getBuildingTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $constructionQueue[$row->building][$row->hours - 1] = (int)$row->amount;
        }

        $this->constructionQueue = $constructionQueue;
        return $constructionQueue;
    }

    public function getConstructionQueueTotal()
    {
        $total = 0;
        $constructionQueue = $this->getConstructionQueue();

        foreach ($constructionQueue as $buildingType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    public function getConstructionQueueTotalByBuilding($building)
    {
        return array_sum($this->getConstructionQueue()[$building]);
    }

    // Military

    public function getMilitaryTrainingQueue()
    {
        //
    }

    public function getMilitaryReturningQueue()
    {
        //
    }
}
