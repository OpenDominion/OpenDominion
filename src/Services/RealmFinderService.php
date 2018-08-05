<?php

namespace OpenDominion\Services;

use DB;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFinderService
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
     * @param int $slotsNeeded
     * @param Bool $forPack
     *
     * @return Realm|null
     */
    public function findRandomRealm(Round $round, Race $race, int $slotsNeeded = 1, bool $forPack = false): ?Realm
    {
        $query = DB::table('realms')
            ->select('realms.id', DB::raw('COUNT(dominions.id) + realms.reserved_slots AS dominion_count'))
            ->leftJoin('dominions', function ($join) use ($round) {
                $join->on('dominions.realm_id', '=', 'realms.id')
                    ->where('dominions.round_id', '=', $round->id);
            })
            ->where('realms.round_id', $round->id)
            ->where('realms.alignment', $race->alignment);

        if ($forPack) {
            $query = $query->where('realms.has_pack', false);
        }

        $query = $query->groupBy('realms.id')
            ->having('dominion_count', '<=', $round->realm_size - $slotsNeeded)
            ->orderBy('dominion_count')
            ->limit(1);

        $results = $query->get();

        if ($results->isEmpty()) {
            return null;
        }

        $realmId = $results->first()->id;

        return Realm::lockForUpdate()->find($realmId);
    }

    public function findRandomRealmForPack(Round $round, Race $race, Pack $pack): ?Realm
    {
        $realm = $this->findRandomRealm($round, $race, $pack->size, true);

        if ($realm !== null) {
            $pack->realm_id = $realm->id;
            $pack->save();

            $realm->has_pack = true;
            $realm->reserved_slots = $round->pack_size;
            $realm->save();

            $pack->load('realm');
        }

        return $realm;
    }
}
