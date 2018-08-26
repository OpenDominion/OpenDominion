<?php

namespace OpenDominion\Services\Dominion\Queue;

use DB;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class LandIncomingQueueService
{
    /** @var LandHelper */
    protected $landHelper;

    /** @var array */
    protected $landIncomingQueue;

    /**
     * LandIncomingQueueService constructor.
     *
     * @param LandHelper $landHelper
     */
    public function __construct(LandHelper $landHelper)
    {
        $this->landHelper = $landHelper;
    }

    /**
     * Returns the land incoming queue of a dominion.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getQueue(Dominion $dominion): array
    {
        if ($this->landIncomingQueue && array_key_exists($dominion->id, $this->landIncomingQueue)) {
            return $this->landIncomingQueue[$dominion->id];
        }

        $rows = DB::table('queue_land_incoming')
            ->where('dominion_id', $dominion->id)
            ->get(['land_type', 'amount', 'hours']);

        $landIncomingQueue = array_fill_keys($this->landHelper->getLandTypes(), array_fill(0, 12, 0));

        foreach ($rows as $row) {
            $landIncomingQueue[$row->land_type][$row->hours - 1] = (int)$row->amount;
        }

        return $this->landIncomingQueue[$dominion->id] = $landIncomingQueue;
    }

    /**
     * Returns the total number of land incoming for a dominion.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getQueueTotal(Dominion $dominion): int
    {
        $total = 0;
        $landIncomingQueue = $this->getQueue($dominion);

        foreach ($landIncomingQueue as $landType => $data) {
            foreach ($data as $hours => $amount) {
                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Returns the total number of a specific land incoming for a dominion.
     *
     * @todo rename to getQueueTotalByLandType
     * @param Dominion $dominion
     * @param string $landType
     * @return int
     */
    public function getQueueTotalByLand(Dominion $dominion, string $landType): int
    {
        return array_sum($this->getQueue($dominion)[$landType]);
    }
}
