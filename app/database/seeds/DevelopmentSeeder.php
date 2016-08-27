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
        $round = $this->createRound();
//        $this->createRealms($round);
//        $this->createUsersAndDominions($round);
    }

    /**
     * @return Round
     */
    private function createRound()
    {
        $this->command->info('Creating round');

        return Round::create([
            'round_league_id' => RoundLeague::where('key', 'standard')->firstOrFail()->id,
            'number' => 1,
            'name' => 'Development Round',
            'start_date' => new DateTime('today midnight'),
            'end_date' => new DateTime('+50 days midnight'),
        ]);
    }

    private function createRealms(Round $round)
    {
        $this->command->info('Creating realms');

        $goodRealm = Realm::create([
            'round_id' => $round->id,
            'alignment' => 'good',
            'number' => 1,
            'name' => 'Good Realm',
        ]);

        $evilRealm = Realm::create([
            'round_id' => $round->id,
            'alignment' => 'evil',
            'number' => 2,
            'name' => 'Evil Realm',
        ]);
    }

    private function createUsersAndDominions(Round $round)
    {
        $this->command->info('Creating users and dominions');

        factory(User::class, 10)->create()->each(function (User $user) {
            //
        });
    }
}
