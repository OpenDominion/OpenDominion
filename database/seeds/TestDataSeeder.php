<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\User;
use OpenDominion\Repositories\DominionRepository;
use OpenDominion\Repositories\UserRepository;

class TestDataSeeder extends Seeder
{
    /**
     * @var DominionRepository
     */
    protected $dominions;

    /**
     * @var
     */
    protected $users;

    /**
     * @param DominionRepository $dominions
     * @param UserRepository     $users
     */
    public function __construct(DominionRepository $dominions, UserRepository $users)
    {
        $this->dominions = $dominions;
        $this->users = $users;
    }

    public function run()
    {
        $user = $this->users->create([
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'remember_token' => '',
        ]);

        $dominion = $this->dominions->create([
            'user_id' => $user->id,
            'name' => 'Test Dominion',
            'ruler_name' => 'Test Ruler',
        ]);
    }
}
