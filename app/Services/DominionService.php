<?php

namespace OpenDominion\Services;

use Atrox\Haikunator;
use DB;
use Exception;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\RealmRepository;

class DominionService
{
    /** @var DominionRepository */
    protected $dominions;

    /** @var RealmRepository */
    protected $realms;

    public function __construct(DominionRepository $dominions, RealmRepository $realms)
    {
        $this->dominions = $dominions;
        $this->realms = $realms;
    }

    /**
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @param string $realmType Currently only 'random'. Future will support packs
     * @param string $name
     * @return Dominion
     * @throws Exception
     */
    public function create(User $user, Round $round, Race $race, $realmType, $name)
    {
        // todo: check if user already has a dominion in this round
        // todo: refactor $realmType into Realm $realm, generate new realm in RealmService from controller instead

        // Get realm
        if ($realmType === 'random') {
            $realmType = $this->findRandomRealm($round, $race);
        } else {
            throw new Exception("Realm '{$realmType}' not supported");
        }

        // Create dominion
        $dominion = $this->dominions->create([
            'user_id' => $user->id,
            'round_id' => $round->id,
            'realm_id' => $realmType->id,
            'race_id' => $race->id,
            'name' => $name,
            'prestige' => 0,
            'peasants' => 1300,
            'peasants_last_hour' => 0,
            'draft_rate' => 10,
            'morale' => 100,
            'resource_platinum' => 100000, // todo: get starting values from configs
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'military_draftees' => 100,
            'land_plain' => 40,
            'land_forest' => 20,
            'land_mountain' => 20,
            'land_hill' => 20,
            'land_swamp' => 20,
            'land_water' => 20,
            'land_cavern' => 20,
            'building_home' => 10,
            'building_alchemy' => 30,
            'building_farm' => 30,
            'building_lumber_yard' => 20,
            // todo: expand with more resources and buildings later
        ]);

        $dominion->updatePrestige();

        // todo: create units

        return $dominion;
    }

    /**
     * @param Round $round
     * @param Race $race
     * @return Realm
     */
    protected function findRandomRealm(Round $round, Race $race)
    {
        // todo: figure out how to do this with repositories
        $results = DB::table('realms')
            ->select('realms.*', DB::raw('COUNT(dominions.id) AS dominion_count'))
            ->leftJoin('dominions', function ($join) {
                $join->on('dominions.realm_id', '=', 'realms.id')
                    ->where('dominions.round_id', '=', 'realms.round_id');
            })
            ->where('realms.round_id', $round->id)
            ->where('realms.alignment', $race->alignment)
            ->groupBy('realms.id')
            ->groupBy('dominions.id')
            ->having('dominion_count', '<', 15)
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

    protected function createRealm(Round $round, $alignment)
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
