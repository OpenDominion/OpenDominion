<?php

namespace OpenDominion\Traits;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Realm;

trait RealmGuardsTrait
{
    /**
     * Guards against the graveyard Realm.
     *
     * @param Realm $realm
     * @throws RuntimeException
     */
    public function guardGraveyardRealm(Realm $realm): void
    {
        if ($realm->number == 0) {
            throw new GameException("You cannot interact with {$realm->name}");
        }
    }
}
