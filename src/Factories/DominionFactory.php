<?php

namespace OpenDominion\Factories;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\RealmFinderService;
use RuntimeException;

class DominionFactory
{
    /** @var RealmFinderService */
    protected $realmFinderService;

    /** @var RealmFactory */
    protected $realmFactory;

    /**
     * DominionFactory constructor.
     *
     * @param RealmFinderService $realmFinderService
     * @param RealmFactory $realmFactory
     */
    public function __construct(RealmFinderService $realmFinderService, RealmFactory $realmFactory)
    {
        $this->realmFinderService = $realmFinderService;
        $this->realmFactory = $realmFactory;
    }

    /**
     * Creates and returns a new Dominion in a valid Realm for the current Round.
     *
     * @see RealmFinderService::findRandomRealm()
     *
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @param string $realmType Currently only 'random'. Future will support packs
     * @param string $name
     *
     * @throws RuntimeException
     * @return Dominion
     */
    public function create(User $user, Round $round, Race $race, string $realmType, string $name): Dominion
    {
        // todo: check if user already has a dominion in this round
        // todo: refactor $realmType into Realm $realm, generate new realm in RealmService from controller instead

        // Try to find a vacant realm
        switch ($realmType) {
            case 'random':
                $realm = $this->realmFinderService->findRandomRealm($round, $race);
                break;

            default:
                throw new RuntimeException("Realm type '{$realmType}' not supported");
        }

        // No vacant realm. Create a new one instead
        if ($realm === null) {
            $realm = $this->realmFactory->create($round, $race->alignment);
        }

        // todo: get starting values from config

        // Create dominion
        $dominion = Dominion::create([
            'user_id' => $user->id,
            'round_id' => $round->id,
            'realm_id' => $realm->id,
            'race_id' => $race->id,

            'name' => $name,
            'prestige' => 250,

            'peasants' => 1300,
            'peasants_last_hour' => 0,

            'draft_rate' => 10,
            'morale' => 100,
            'spy_strength' => 100,
            'wizard_strength' => 100,

            'resource_platinum' => 100000,
            'resource_food' => 15000,
            'resource_lumber' => 15000,
            'resource_mana' => 0,
            'resource_ore' => 0,
            'resource_gems' => 10000,
            'resource_tech' => 0,
            'resource_boats' => 0,

            'improvement_science' => 0,
            'improvement_keep' => 0,
            'improvement_towers' => 0,
            'improvement_forges' => 0,
            'improvement_walls' => 0,
            'improvement_irrigation' => 0,

            'military_draftees' => 100,
            'military_unit1' => 0,
            'military_unit2' => 150,
            'military_unit3' => 0,
            'military_unit4' => 0,
            'military_spies' => 25,
            'military_wizards' => 25,
            'military_archmages' => 0,

            'land_plain' => 110,
            'land_mountain' => 20,
            'land_swamp' => 20,
            'land_cavern' => 20,
            'land_forest' => 40,
            'land_hill' => 20,
            'land_water' => 20,

            'building_home' => 10,
            'building_alchemy' => 30,
            'building_farm' => 30,
            'building_smithy' => 0,
            'building_masonry' => 0,
            'building_ore_mine' => 0,
            'building_gryphon_nest' => 0,
            'building_tower' => 0,
            'building_wizard_guild' => 0,
            'building_temple' => 0,
            'building_diamond_mine' => 0,
            'building_school' => 0,
            'building_lumberyard' => 20,
            'building_forest_haven' => 0,
            'building_factory' => 0,
            'building_guard_tower' => 0,
            'building_shrine' => 0,
            'building_barracks' => 0,
            'building_dock' => 0,
        ]);

        return $dominion;
    }
}
