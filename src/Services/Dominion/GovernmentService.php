<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\HistoryService;

class GovernmentService
{
    /**
     * Gets votes for Realm monarchy by Dominion.
     *
     * @param Realm $realm
     * @return array
     */
    public function getMonarchVotes(Realm $realm): array
    {
        $votes = $realm->dominions->groupBy('monarch_dominion_id');

        $results = [];
        foreach ($votes as $monarch => $dominions) {
            if ($monarch != null) {
                $results[$monarch] = count($dominions);
            }
        }

        return $results;
    }

    /**
     * Check if a new monarch has been elected for a Realm.
     *
     * @param Realm $realm
     * @return bool
     */
    public function checkMonarchVotes(Realm $realm): bool
    {
        $currentMonarch = $realm->monarch();
        $votes = $this->getMonarchVotes($realm);
        $totalVotes = array_sum($votes);

        $leaderId = null;
        $leaderVotes = 0;
        foreach ($votes as $dominion => $total) {
            if ($total > $leaderVotes) {
                $leaderId = $dominion;
                $leaderVotes = $total;
            }
        }

        if ($leaderVotes > floor($totalVotes / 2)) {
            $this->setRealmMonarch($realm, $leaderId);
            return true;
        } else {
            $this->setRealmMonarch($realm, null);
            return true;
        }

        return false;
    }

    /**
     * Sets the Realm's monarch_dominion_id.
     *
     * @param Realm $realm
     * @param int $monarch_dominion_id
     */
    protected function setRealmMonarch(Realm $realm, ?int $monarch_dominion_id)
    {
        $realm->monarch_dominion_id = $monarch_dominion_id;
        $realm->save();
    }
}
