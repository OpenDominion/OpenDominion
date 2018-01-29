<?php

use Illuminate\Database\Seeder;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;
use Spatie\Permission\Models\Role;

class DevelopmentSeeder extends Seeder
{
    /** @var DominionFactory */
    protected $dominionFactory;

    /**
     * DevelopmentSeeder constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct(DominionFactory $dominionFactory)
    {
        $this->dominionFactory = $dominionFactory;
    }

    public function run()
    {
        $this->createRoles();
        $user = $this->createUser();
        $round = $this->createRound();
        $this->createRealmAndDominion($user, $round);
    }

    protected function createRoles()
    {
        $this->command->info('Creating user roles');

        Role::create(['name' => 'Developer']);
        Role::create(['name' => 'Administrator']);
        Role::create(['name' => 'Moderator']);
    }

    protected function createUser(): User
    {
        $this->command->info('Creating test user');

        $user = User::create([
            'email' => 'email@example.com',
            'password' => bcrypt('test'),
            'display_name' => 'Dev User',
            'activated' => true,
            'activation_code' => str_random(),
        ]);

        $user->assignRole(['Developer', 'Administrator', 'Moderator']);

        $this->command->info("User created. You may login with with email {$user->email} and password 'test'");

        return $user;
    }

    protected function createRound(): Round
    {
        $this->command->info('Creating development round');

        return Round::create([
            'round_league_id' => RoundLeague::where('key', 'standard')->firstOrFail()->id,
            'number' => 1,
            'name' => 'Dev Round',
            'start_date' => new DateTime('today midnight'),
            'end_date' => new DateTime('+50 days midnight'),
        ]);
    }

    protected function createRealmAndDominion(User $user, Round $round): Dominion
    {
        $this->command->info('Creating realm and dominion');

        return $this->dominionFactory->create(
            $user,
            $round,
            Race::where('name', 'Human')->firstOrFail(),
            'random',
            'Dev Dominion'
        );
    }
}
