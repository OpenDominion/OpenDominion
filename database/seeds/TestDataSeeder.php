<?php

use Illuminate\Database\Seeder;
use OpenDominion\Creators\DominionCreator;
use OpenDominion\Models\User;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\UserRepository;

class TestDataSeeder extends Seeder
{
    /**
     * @var DominionCreator
     */
    protected $dominionCreator;

    /**
     * @var DominionRepository
     */
    protected $dominions;

    /**
     * @var UserRepository
     */
    protected $users;

    /**
     * @param DominionCreator    $dominionCreator
     * @param DominionRepository $dominions
     * @param UserRepository     $users
     */
    public function __construct(DominionCreator $dominionCreator, DominionRepository $dominions, UserRepository $users)
    {
        $this->dominionCreator = $dominionCreator;
        $this->dominions = $dominions;
        $this->users = $users;
    }

    public function run()
    {
        $user = $this->users->create([
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'display_name' => 'Tester',
        ]);

        $this->dominionCreator->create($user, 'Test Dominion');
    }
}
