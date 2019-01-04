<?php

namespace OpenDominion\Services;

use DB;
use Illuminate\Database\Query\JoinClause;
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
     *
     * @return Realm|null
     */
    public function findRandomRealm(Round $round, Race $race, int $slotsNeeded = 1): ?Realm
    {
        $realms = Realm::query()
            ->with('packs.dominions')
            ->withCount('dominions')
            ->leftJoin('packs', function (JoinClause $join) {
                $join->on('packs.realm_id', '=', 'realms.id')
                    ->where('packs.closed_at', '>', now());
            })
            ->where([
                'realms.round_id' => $round->id,
                'realms.alignment' => $race->alignment,
            ])
            ->groupBy('realms.id')
            ->having(DB::raw('dominions_count + coalesce(packs.size, 1) - 1'), '<', $round->realm_size)
            ->orderBy('number')
            ->get();

        foreach ($realms as $realm) {
            $availableSlots = ($round->realm_size - $realm->dominions_count);

            if (($realm->pack !== null) && !$realm->pack->isClosed()) {
                $availableSlots -= ($realm->pack->size - $realm->pack->dominions->count());
            }

            if ($availableSlots >= $slotsNeeded) {
                return Realm::find($realm->id); // return fresh copy
            }
        }

        return null;
    }

    public function findRandomRealmForPack(Round $round, Race $race, Pack $pack): ?Realm
    {
        // todo: move this to dominionfactory instead
        $realm = $this->findRandomRealm($round, $race, $pack->size);

        if ($realm !== null) {
            $pack->realm_id = $realm->id;
            $pack->save();

//            $realm->has_pack = true;
//            $realm->reserved_slots = $round->pack_size;
//            $realm->save();

//            $pack->load('realm');
        }

        return $realm;
    }
}
