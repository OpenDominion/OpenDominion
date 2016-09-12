<?php

namespace OpenDominion\Services;

use Atrox\Haikunator;
use DB;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Repositories\RealmRepository;

class RealmService
{
    /** @var RealmRepository */
    protected $realms;

    /**
     * RealmService constructor.
     *
     * @param RealmRepository $realms
     */
    public function __construct(RealmRepository $realms)
    {
        $this->realms = $realms;
    }

    /**
     * Finds and returns the first best realm for a new Dominion to settle in.
     *
     * The new Dominion currently gets placed in a random Realm of the same alignment of its Race, up to a max of 12
     * Dominions in that realm.
     *
     * @see DominionService::create()
     *
     * @param Round $round
     * @param Race $race
     *
     * @return Realm
     */
    public function findRandomRealm(Round $round, Race $race)
    {
        // todo: figure out how to do this with repositories
        $results = DB::table('realms')
            ->select('realms.*', DB::raw('COUNT(dominions.id) AS dominion_count'))
            ->leftJoin('dominions', function ($join) use ($round) {
                $join->on('dominions.realm_id', '=', 'realms.id')
                    ->where('dominions.round_id', '=', $round->id);
            })
            ->where('realms.round_id', $round->id)
            ->where('realms.alignment', $race->alignment)
            ->groupBy('realms.id')
            ->having('dominion_count', '<', 12)
            ->orderBy('dominion_count')
            ->limit(1)
            ->get();

        if ($results->isEmpty()) {
            $realm = $this->createRealm($round, $race->alignment);

        } else {
            $realm = Realm::findOrFail($results->first()->id);
        }

        return $realm;
    }

    /**
     * Creates and returns a new Realm in a Round based on alignment.
     *
     * @param Round $round
     * @param string $alignment
     *
     * @return Realm
     */
    public function createRealm(Round $round, $alignment)
    {
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

        $realm = $this->realms->create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => $number,
            'name' => $realmName,
        ]);

        return $realm;
    }
}
