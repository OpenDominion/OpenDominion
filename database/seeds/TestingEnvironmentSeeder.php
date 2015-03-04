<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\User;

class TestingEnvironmentSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'email' => 'test@example.com',
            'password' => Hash::make('test'),
            'remember_token' => '',
        ]);
    }
}
