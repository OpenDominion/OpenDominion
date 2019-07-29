<?php

namespace OpenDominion\Factories;

use Atrox\Haikunator;
use DB;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFactory
{
    /**
     * Creates and returns a new Realm in a Round based on alignment.
     *
     * @param Round $round
     * @param string $alignment
     * @param Pack|null $pack
     * @return Realm
     */
    public function create(Round $round, string $alignment, ?Pack $pack = null): Realm
    {
        // todo: whitelist $alignment?
        // todo: repositories?
        $results = DB::table('realms')
            ->select(DB::raw('MAX(realms.number) AS max_realm_number'))
            ->where('round_id', $round->id)
            ->limit(1)
            ->get();

        if ($results === null) {
            $number = 1;
        } else {
            $number = ((int)$results[0]->max_realm_number + 1);
        }

        if($round->mixed_alignment) {
            $alignment = 'neutral';
        }

        $realmName = ucwords(Haikunator::haikunate([
            'tokenLength' => 0,
            'delimiter' => ' '
        ]));

        $realm = Realm::create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => $number,
            'name' => $realmName
        ]);

        if ($pack !== null) {
            $pack->update(['realm_id' => $realm->id]);
            $pack->load('realm');
        }

        return $realm;
    }
}
