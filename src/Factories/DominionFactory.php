<?php

namespace OpenDominion\Factories;

use Exception;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Services\RealmService;

class DominionFactory
{
    /** @var DominionRepository */
    protected $dominions;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var RealmService */
    protected $realmService;

    /**
     * DominionFactory constructor.
     *
     * @param DominionRepository $dominions
     * @param NetworthCalculator $networthCalculator
     * @param RealmService $realmService
     */
    public function __construct(DominionRepository $dominions, NetworthCalculator $networthCalculator, RealmService $realmService)
    {
        $this->dominions = $dominions;
        $this->networthCalculator = $networthCalculator;
        $this->realmService = $realmService;
    }

    /**
     * Creates and returns a new Dominion in a valid Realm for the current Round.
     *
     * @see RealmService::findRandomRealm()
     *
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @param string $realmType Currently only 'random'. Future will support packs
     * @param string $name
     *
     * @throws Exception
     * @return Dominion
     */
    public function create(User $user, Round $round, Race $race, $realmType, $name)
    {
        // todo: check if user already has a dominion in this round
        // todo: refactor $realmType into Realm $realm, generate new realm in RealmService from controller instead

        // Get realm
        if ($realmType === 'random') {
            $realmType = $this->realmService->findRandomRealm($round, $race);
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
            'networth' => 0,
            'prestige' => 0,
            'peasants' => 1300,
            'peasants_last_hour' => 0,
            'draft_rate' => 10,
            'morale' => 100,
            'resource_platinum' => 100000, // todo: get starting values from configs/data
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'military_draftees' => 100,
            'military_unit1' => 0,
            'military_unit2' => 150,
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 25,
            'military_wizards' => 25,
            'military_archmages' => 0,
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

        $this->updateNetworth($dominion);

        return $dominion;
    }

    /**
     * Calculates and updates a Dominion's networth.
     *
     * @param Dominion $dominion
     */
    public function updateNetworth(Dominion $dominion)
    {
        $dominion->networth = $this->networthCalculator->getDominionNetworth($dominion);
        $dominion->save();
    }
}
