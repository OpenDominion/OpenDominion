<?php

namespace OpenDominion\Services;

use DB;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionAwareTrait;

class DominionQueueService
{
    use DominionAwareTrait;

    /** @var LandHelper */
    protected $landHelper;

    /** @var array */
    protected $explorationQueue;

    public function __construct(Dominion $dominion)
    {
        $this->setDominion($dominion);

        $this->landHelper = app()->make(LandHelper::class);
    }

    public function getExplorationQueue()
    {
        if ($this->explorationQueue) {
            return $this->explorationQueue;
        }

        $rows = DB::table('queue_exploration')
            ->where('dominion_id', $this->dominion->id)
            ->get(['land_type', 'amount', 'hours']);

        $return = array_fill_keys($this->landHelper->getLandTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $return[$row->land_type][$row->hours - 1] = (int)$row->amount;
        }

        $this->explorationQueue = $return;
        return $return;
    }

    public function getConstructionQueue()
    {
        //
    }

    public function getMilitaryTrainingQueue()
    {
        //
    }

    public function getMilitaryReturningQueue()
    {
        //
    }
}
