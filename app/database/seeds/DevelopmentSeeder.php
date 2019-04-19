<?php

use Illuminate\Database\Seeder;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;

class DevelopmentSeeder extends Seeder
{
    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var string */
    protected $userPassword = 'secret';

    /**
     * DevelopmentSeeder constructor.
     *
     * @param DominionFactory $dominionFactory
     */
    public function __construct(DominionFactory $dominionFactory)
    {
        $this->dominionFactory = $dominionFactory;
    }

    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        DB::transaction(function () {
            $user = $this->createUser();
            $this->createRound();
            $this->createRealmAndDominion($user);

            $this->command->info(<<<INFO

Done seeding data.
A development round, user and dominion have been created for your convenience.
You may login with email '{$user->email}' and password '{$this->userPassword}'.

INFO
            );
        });
    }

    protected function createUser(): User
    {
        $this->command->info('Creating dev user');

        $user = User::create([
            'email' => 'email@example.com',
            'password' => bcrypt($this->userPassword),
            'display_name' => 'Dev User',
            'activated' => true,
            'activation_code' => str_random(),
        ]);

        $user->assignRole(['Developer', 'Administrator', 'Moderator']);

        return $user;
    }

    protected function createRound(): Round
    {
        $this->command->info('Creating development round');

        $startDate = today();

        return Round::create([
            'round_league_id' => RoundLeague::where('key', 'standard')->firstOrFail()->id,
            'number' => 1,
            'name' => 'Dev Round',
            'start_date' => $startDate,
            'end_date' => (clone $startDate)->addDays(49), // 50 minus today
        ]);
    }

    protected function createRealmAndDominion(User $user): Dominion
    {
        $this->command->info('Creating realm');

        $realmFactory = app(RealmFactory::class);

        $round = Round::firstOrFail();
        $humanRace = Race::where('name', 'Human')->firstOrFail();

        $realm = $realmFactory->create($round, $humanRace->alignment);

        $this->command->info('Creating dominion');

        return $this->dominionFactory->create(
            $user,
            $realm,
            $humanRace,
            'Test Ruler Name',
            'Test Dominion Name'
        );
    }
}
