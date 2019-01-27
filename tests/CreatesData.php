<?php

namespace OpenDominion\Tests;

use Artisan;
use Carbon\Carbon;
use CoreDataSeeder;
use OpenDominion\Console\Commands\Game\DataSyncCommand;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\Dominion\SelectorService;

trait CreatesData
{
    /**
     * Seeds the database with core data (races, units etc).
     */
    public function seedDatabase()
    {
        $this->seed(CoreDataSeeder::class);

        Artisan::call(DataSyncCommand::class);
    }

    /**
     * Creates a user for testing purposes.
     *
     * @param string|null $password
     * @param array $attributes
     * @return User
     */
    protected function createUser($password = null, array $attributes = [])
    {
        if ($password !== null) {
            $attributes['password'] = bcrypt($password);
        }

        return factory(User::class)->create($attributes);
    }

    /**
     * Creates and impersonates a user for testing purposes.
     *
     * @param string|null $password
     * @param array $attributes
     * @return User
     */
    protected function createAndImpersonateUser($password = null, array $attributes = [])
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
    protected function createRound($startDate = 'today', $endDate = '+50 days')
    {
        // todo: RoundFactory

        $round = Round::create([
            'round_league_id' => 1,
            'number' => 1,
            'name' => 'Testing Round',
            'start_date' => new Carbon($startDate . ' midnight'),
            'end_date' => new Carbon($endDate . ' midnight'),
            'realm_size' => 12,
            'pack_size' => 6
        ]);

        return $round;
    }

    /**
     * @param Round $round
     * @param string $alignment 'good' or 'evil'
     * @return Realm
     */
    protected function createRealm(Round $round, $alignment = 'good')
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
     * @param User $user
     * @param Round $round
     * @param Race|null $race
     * @param Realm|null $realm
     * @return Dominion
     */
    protected function createDominion(User $user, Round $round, ?Race $race = null, ?Realm $realm = null): Dominion
    {
        $faker = \Faker\Factory::create();

        /** @var DominionFactory $dominionFactory */
        $dominionFactory = $this->app->make(DominionFactory::class);

        if ($realm === null) {
            /** @var RealmFactory $realmFactory */
            $realmFactory = $this->app->make(RealmFactory::class);

            $realm = $realmFactory->create(
                $round,
                $race->alignment ?? 'good'
            );
        }

        return $dominionFactory->create(
            $user,
            $realm,
            $race ?? Race::first(),
            $faker->name,
            $faker->company
        );
    }

    /**
     * @param Dominion $dominion
     * @return Dominion
     */
    protected function selectDominion(Dominion $dominion)
    {
        $dominionSelectorService = $this->app->make(SelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion);

        return $dominion;
    }

    /**
     * @param User $user
     * @param Round $round
     * @param Race $race
     * @return Dominion
     */
    protected function createAndSelectDominion(User $user, Round $round, Race $race = null)
    {
        $dominion = $this->createDominion($user, $round, $race);
        return $this->selectDominion($dominion);
    }
}
