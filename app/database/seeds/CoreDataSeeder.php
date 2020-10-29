<?php

use Illuminate\Database\Seeder;
use OpenDominion\Models\MessageBoard\Category;
use OpenDominion\Models\RoundLeague;

class CoreDataSeeder extends Seeder
{
    private $roundLeagueIds = [];

    /**
     * Run the database seeds.
     *
     * @throws Throwable
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->createRoundLeagues();
            $this->createMessageBoard();
        });
    }

    protected function createRoundLeagues(): void
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

    protected function createMessageBoard(): void
    {
        $this->command->info('Creating message board categories');

        Category::create([
            'name' => 'Announcements',
            'slug' => 'announcements',
            'role_required' => 'Moderator',
        ]);

        Category::create([
            'name' => 'General',
            'slug' => 'general',
        ]);
    }
}
