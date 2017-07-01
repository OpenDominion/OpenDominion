<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;

class DevelopmentSeeder extends Seeder
{
    public function run()
    {
        $this->createRound();
        $this->createUser();
    }

    private function createRound()
    {
        $this->command->info('Creating development round');

        Round::create([
            'round_league_id' => RoundLeague::where('key', 'standard')->firstOrFail()->id,
            'number' => 1,
            'name' => 'Development Round',
            'start_date' => new DateTime('today midnight'),
            'end_date' => new DateTime('+50 days midnight'),
        ]);
    }

    private function createUser()
    {
        $this->command->info('Creating development user: dev/password');

        User::create([
            'email' => 'dev@example.com',
            'password' => '$2y$10$qKx74g1ba87kyydtqrhJD.knVZj9iZolvfQhO4FDSukZ4jmU7U.tO',
            'activation_code' => 'jpabCkn7hy40Cxa4',
            'display_name' => 'dev',
            'activated' => true,
        ]);
    }
}
