<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\RoundLeague;

class CoreDataSeeder extends Seeder
{
    private $roundLeagueIds = [];

    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run()
    {
        DB::transaction(function () {
            $this->createRoundLeagues();
        });
    }

    protected function createRoundLeagues()
    {
        $this->command->info('Creating round leagues');

        $json = json_decode(file_get_contents(base_path('app/data/round_leagues.json')));

        foreach ($json->round_leagues as $row) {
            $roundLeague = RoundLeague::create([
                'key' => $row->key,
                'description' => $row->description,
            ]);

            $this->roundLeagueIds[$roundLeague->key] = $roundLeague->id;
        }
    }
}
