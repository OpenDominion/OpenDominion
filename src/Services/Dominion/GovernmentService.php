<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\HistoryService;

class GovernmentService
{
    public const WAR_ACTIVE_WAIT_IN_HOURS = 24;
    public const WAR_CANCEL_WAIT_IN_HOURS = 48;

    /**
     * Gets votes for Realm monarchy by Dominion.
     *
     * @param Realm $realm
     * @return array
     */
    public function getMonarchVotes(Realm $realm): array
    {
        $votes = $realm->dominions->groupBy('monarchy_vote_for_dominion_id');

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
        if ($realm->monarch) {
            $currentMonarchId = $realm->monarch->id;
        } else {
            $currentMonarchId = null;
        }
        $votes = $this->getMonarchVotes($realm);
        $totalVotes = array_sum($votes);

        $leaderId = null;
        $leaderVotes = 0;
        $currentMonarchVotes = 0;
        foreach ($votes as $dominionId => $total) {
            if ($currentMonarchId == $dominionId) {
                $currentMonarchVotes = $total;
            }
            if ($total > $leaderVotes) {
                $leaderId = $dominionId;
                $leaderVotes = $total;
            }
        }

        if ($leaderId == $currentMonarchId || $leaderVotes == $currentMonarchVotes) {
            return false;
        } elseif ($leaderVotes > floor($totalVotes / 3)) {
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

    /**
     * Checks for existing war declaration by realm
     *
     * @param Realm $realm
     */
    public function hasDeclaredWar(Realm $realm): bool
    {
        if ($realm->war_realm_id !== null) {
            return true;
        }
        return false;
    }

    /**
     * Checks if war can be declared by realm
     *
     * @param Realm $realm
     */
    public function canDeclareWar(Realm $realm): bool
    {
        if ($realm->war_realm_id === null) {
            return true;
        }
        return false;
    }

    /**
     * Returns the hour of war declaration
     *
     * @param Realm $realm
     */
    public function getWarDeclaredAt(Realm $realm): string
    {
        if ($realm->war_realm_id === null) {
            return '';
        }

        $modifiedDate = Carbon::parse($realm->war_active_at);
        $declaredDate = $modifiedDate->addHours(-self::WAR_ACTIVE_WAIT_IN_HOURS);

        return $declaredDate->startOfHour();
    }

    /**
     * Returns the number of hours remaining before war can be canceled
     *
     * @param Realm $realm
     */
    public function getHoursBeforeCancelWar(Realm $realm): int
    {
        if (!$realm->war_realm_id) {
            return 0;
        }

        $modifiedDate = Carbon::parse($realm->war_active_at);
        $cancelDate = $modifiedDate->addHours(self::WAR_CANCEL_WAIT_IN_HOURS);

        if ($cancelDate > now()->startOfHour()) {
            return $cancelDate->diffInHours(now()->startOfHour());
        }

        return 0;
    }

    /**
     * Returns the number of hours remaining before war becomes active
     *
     * @param Realm $realm
     */
    public function getHoursBeforeWarActive(Realm $realm)
    {
        $date = Carbon::parse($realm->war_active_at);
        if ($date->startOfHour() <= now()->startOfHour()) {
            return 0;
        }
        return $date->diffInHours(now()->startOfHour());
    }

    /**
     * Returns war status between two realms
     *
     * @param Realm $realm
     * @param Realm $target
     */
    public function isAtWarWithRealm(Realm $realm, Realm $target): bool
    {
        if ($realm->war_realm_id == $target->id && $this->getHoursBeforeWarActive($realm) === 0) {
            return true;
        }

        if ($target->war_realm_id == $realm->id && $this->getHoursBeforeWarActive($target) === 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns mutual war status between two realms
     *
     * @param Realm $realm
     * @param Realm $target
     */
    public function isAtMutualWarWithRealm(Realm $realm, Realm $target): bool
    {
        if (
            $realm->war_realm_id == $target->id &&
            $this->getHoursBeforeWarActive($realm) === 0 &&
            $target->war_realm_id == $realm->id &&
            $this->getHoursBeforeWarActive($target) === 0
        ) {
            return true;
        }

        return false;
    }
}
