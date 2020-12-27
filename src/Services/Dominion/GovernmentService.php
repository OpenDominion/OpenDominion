<?php

namespace OpenDominion\Services\Dominion;

use Carbon\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;

class GovernmentService
{
    public const WAR_ACTIVE_WAIT_IN_HOURS = 24;
    public const WAR_INACTIVE_WAIT_IN_HOURS = 6;
    public const WAR_CANCEL_WAIT_IN_HOURS = 48;
    public const WAR_REDECLARE_WAIT_IN_HOURS = 48;

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
        if ($realm->warsOutgoing()->engaged()->exists()) {
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
        if (!$this->hasDeclaredWar($realm)) {
            return true;
        }
        return false;
    }

    /**
     * Returns the hour of war declaration
     *
     * @param RealmWar $war
     */
    public function getWarDeclaredAt(RealmWar $war): string
    {
        if ($war->created_at == null) {
            return '';
        }

        return $war->created_at->startOfHour();
    }

    /**
     * Returns the number of hours remaining before war can be canceled
     *
     * @param RealmWar $war
     */
    public function getHoursBeforeCancelWar(RealmWar $war): int
    {
        $cancelDate = $war->active_at->addHours(self::WAR_CANCEL_WAIT_IN_HOURS);

        if ($cancelDate > now()->startOfHour()) {
            return $cancelDate->diffInHours(now()->startOfHour());
        }

        return 0;
    }

    /**
     * Returns the number of hours remaining before war becomes active
     *
     * @param RealmWar $war
     */
    public function getHoursBeforeWarActive(RealmWar $war)
    {
        if ($war->active_at->startOfHour() <= now()->startOfHour()) {
            return 0;
        }
        return $war->active_at->diffInHours(now()->startOfHour());
    }

    /**
     * Returns the number of hours remaining before war becomes inactive
     *
     * @param RealmWar $war
     */
    public function getHoursBeforeWarInactive(RealmWar $war)
    {
        if ($war->inactive_at == null || $war->inactive_at->startOfHour() <= now()->startOfHour()) {
            return 0;
        }
        return $war->inactive_at->diffInHours(now()->startOfHour());
    }

    /**
     * Returns war status between two realms
     *
     * @param Realm $realm
     * @param Realm $target
     */
    public function isAtWar(Realm $realm, Realm $target): bool
    {
        if (
            $realm->warsOutgoing()->engaged()->where('target_realm_id', $target->id)->exists() ||
            $target->warsOutgoing()->engaged()->where('source_realm_id', $realm->id)->exists()
        ) {
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
    public function isAtMutualWar(Realm $realm, Realm $target): bool
    {
        if (
            $realm->warsOutgoing()->engaged()->where('target_realm_id', $target->id)->exists() &&
            $target->warsOutgoing()->engaged()->where('source_realm_id', $realm->id)->exists()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns war bonus status between two realms
     *
     * @param Realm $source
     * @param Realm $target
     */
    public function isWarEscalated(Realm $source, Realm $target): bool
    {
        if (
            $source->warsOutgoing()->escalated()->where('target_realm_id', $target->id)->exists()
        ) {
            return true;
        }

        return false;
    }

    /**
     * Returns mutual war bonus status between two realms
     *
     * @param Realm $realm
     * @param Realm $target
     */
    public function isMutualWarEscalated(Realm $realm, Realm $target): bool
    {
        if (
            $realm->warsOutgoing()->escalated()->where('target_realm_id', $target->id)->exists() &&
            $target->warsOutgoing()->escalated()->where('source_realm_id', $realm->id)->exists()
        ) {
            return true;
        }

        return false;
    }
}
