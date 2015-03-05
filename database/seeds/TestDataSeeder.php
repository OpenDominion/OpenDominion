<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\User;

class TestDataSeeder extends Seeder
{
    public function __construct()
    {
    }

    public function run()
    {
        User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'remember_token' => '',
        ]);
    }
}
