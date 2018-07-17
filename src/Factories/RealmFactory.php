<?php

namespace OpenDominion\Factories;

use Atrox\Haikunator;
use DB;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFactory
{
    /**
     * Creates and returns a new Realm in a Round based on alignment.
     *
     * @param Round $round
     * @param string $alignment
     *
     * @return Realm
     */
    public function create(Round $round, string $alignment, Pack $pack = null): Realm
    {
        // todo: whitelist $alignment?
        // todo: repositories?
        $results = DB::table('realms')
            ->select(DB::raw('MAX(realms.number) AS max_realm_number'))
            ->where('round_id', $round->id)
            ->limit(1)
            ->get();

        if (empty($results)) {
            $number = 1;
        } else {
            $number = ((int)$results[0]->max_realm_number + 1);
        }

        $realmName = ucwords(Haikunator::haikunate([
            'tokenLength' => 0,
            'delimiter' => ' '
        ]));
        
        $hasPack = $pack !== null ? true : false;
        $reservedSlots = $pack->size ?? 0;
        $realm = Realm::create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => $number,
            'name' => $realmName,
            'has_pack' => $hasPack,
            'reserved_slots' => $reservedSlots,
        ]);
        
        if($pack !== null){
            $pack->realm_id = $realm->id;
            $pack->save();
        }

        return $realm;
    }
}
