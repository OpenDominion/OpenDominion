<?php

namespace OpenDominion\Tests\Traits;

use Carbon\Carbon;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Services\RealmFinderService;

trait CreatesData
{
    /**
     * Creates a user for testing purposes.
     *
     * @param string|null $password
     * @param array $attributes
     * @return User
     */
    protected function createUser(?string $password = null, array $attributes = []): User
    {
        if ($password !== null) {
            $attributes['password'] = bcrypt($password);
        }

        return User::factory()->create($attributes);
    }

    /**
     * Creates and impersonates a user for testing purposes.
     *
     * @param string|null $password
     * @param array $attributes
     * @return User
     */
    protected function createAndImpersonateUser(?string $password = null, array $attributes = []): User
    {
        $user = $this->createUser($password, $attributes);
        $this->be($user);
        return $user;
    }

    /**
     * Creates a round for testing purposes.
     *
     * @param string $startDate Carbon-parsable string
     * @param string $endDate Carbon-parsable string
     * @return Round
     */
    protected function createRound(string $startDate = '+0 days', string $endDate = '+47 days'): Round
    {
        // todo: RoundFactory

        $round = Round::create([
            'round_league_id' => 1,
            'number' => 1,
            'name' => 'Testing Round',
            'start_date' => new Carbon($startDate),
            'end_date' => new Carbon($endDate),
            'realm_size' => 12,
            'pack_size' => 6
        ]);

        return $round;
    }

    /**
     * Creates a realm for testing purposes.
     *
     * @param Round $round
     * @param string $alignment 'good' or 'evil'
     * @return Realm
     */
    protected function createRealm(Round $round, string $alignment = 'good'): Realm
    {
        // todo: RealmFactory

        $realm = Realm::create([
            'round_id' => $round->id,
            'alignment' => $alignment,
            'number' => 1,
            'name' => 'Testing Realm',
        ]);

        return $realm;
    }

    /**
     * Creates a dominion for testing purposes.
     *
     * @param User $user
     * @param Round $round
     * @param Race|null $race
     * @param Realm|null $realm
     * @return Dominion
     */
    protected function createDominion(User $user, Round $round, ?Race $race = null, ?Realm $realm = null): Dominion
    {
        $faker = \Faker\Factory::create();

        if ($race === null) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $race = Race::where('name', 'Human')->firstOrFail();
        }

        if ($realm === null) {
            /** @var RealmFinderService $realmFinderService */
            $realmFinderService = $this->app->make(RealmFinderService::class);

            $realm = $realmFinderService->findRealm($round, $race, $user);

            if ($realm === null) {
                /** @var RealmFactory $realmFactory */
                $realmFactory = $this->app->make(RealmFactory::class);

                $realm = $realmFactory->create(
                    $round,
                    $race->alignment
                );
            }
        }

        /** @var DominionFactory $dominionFactory */
        $dominionFactory = $this->app->make(DominionFactory::class);

        return $dominionFactory->create(
            $user,
            $realm,
            $race,
            $faker->name,
            $faker->unique()->company
        );
    }

    /**
     * Selects a dominion for testing purposes.
     *
     * @param Dominion $dominion
     * @return Dominion
     */
    protected function selectDominion(Dominion $dominion): Dominion
    {
        /** @var SelectorService $dominionSelectorService */
        $dominionSelectorService = $this->app->make(SelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion);

        return $dominion;
    }

    /**
     * Creates and selects a dominion for testing purposes.
     *
     * @param User $user
     * @param Round $round
     * @param Race|null $race
     * @return Dominion
     */
    protected function createAndSelectDominion(User $user, Round $round, ?Race $race = null, ?Realm $realm = null): Dominion
    {
        $dominion = $this->createDominion($user, $round, $race, $realm);
        return $this->selectDominion($dominion);
    }

    /**
     * Creates a dominion with legacy starting resources for tests that expect old values.
     *
     * @param User $user
     * @param Round $round
     * @param Race|null $race
     * @param Realm|null $realm
     * @return Dominion
     */
    protected function createDominionWithLegacyStats(User $user, Round $round, ?Race $race = null, ?Realm $realm = null): Dominion
    {
        // First create a normal dominion
        $dominion = $this->createDominion($user, $round, $race, $realm);

        // Override with legacy values that tests expect
        $dominion->update([
            'peasants' => 1300,
            'resource_platinum' => 100000,
            'military_draftees' => 100,
            'military_unit2' => 150,
            'military_spies' => 25,
            'military_wizards' => 25,

            // Legacy land distribution (total 250 vs new 560/350)
            'land_plain' => 100,
            'land_cavern' => 20,
            'land_hill' => 20,
            'land_mountain' => 20,
            'land_swamp' => 20,
            'land_forest' => 40,
            'land_water' => 20,

            'building_home' => 10,
            'building_alchemy' => 30,
            'building_farm' => 30,
            'building_lumberyard' => 20,

            // Disable protection for tests
            'protection_type' => 'advanced',
            'protection_ticks' => 72, // Ensure we're not in building phase
            'protection_ticks_remaining' => 0,
            'protection_finished' => true,
        ]);

        if ($race !== null) {
            $homeland = $race->home_land_type;
            $homelandAttr = "land_{$homeland}";
        } else {
            $homelandAttr = "land_plain";
        }
        $dominion->update([$homelandAttr => $dominion->{$homelandAttr} + 10]);

        return $dominion;
    }

    /**
     * Creates and selects a dominion with legacy starting resources for tests.
     *
     * @param User $user
     * @param Round $round
     * @param Race|null $race
     * @param Realm|null $realm
     * @return Dominion
     */
    protected function createAndSelectDominionWithLegacyStats(User $user, Round $round, ?Race $race = null, ?Realm $realm = null): Dominion
    {
        $dominion = $this->createDominionWithLegacyStats($user, $round, $race, $realm);
        return $this->selectDominion($dominion);
    }
}
