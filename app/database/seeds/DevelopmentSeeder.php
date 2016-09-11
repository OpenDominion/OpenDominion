<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Models\User;

class DevelopmentSeeder extends Seeder
{
    public function run()
    {
        $this->createRound();
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
}
