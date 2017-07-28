<?php

namespace OpenDominion\Contracts\Services;

use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

interface RealmFinderService
{
    /**
     * Finds and returns the first best realm for a new Dominion to settle in.
     *
     * Up to 12 Dominions can exist in a realm.
     *
     * @see DominionFactory::create()
     *
     * @param Round $round
     * @param Race $race
     *
     * @return Realm|null
     */
    public function findRandom(Round $round, Race $race);
}
