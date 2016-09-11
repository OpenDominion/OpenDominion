<?php

namespace OpenDominion\Tests\Feature;

use Artisan;
use CoreDataSeeder;
use DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class TickTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testMoraleTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->morale = 64;
        $dominion->save();

        // Test +6 morale below 70
        Artisan::call('game:tick');
        $this->seeInDatabase('dominions', ['id' => $dominion->id, 'morale' => 70]);

        // Test +3 morale above 70
        Artisan::call('game:tick');
        $this->seeInDatabase('dominions', ['id' => $dominion->id, 'morale' => 73]);
    }

    public function testQueuesTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->land_plain = 0;
        $dominion->building_home = 0;
        $dominion->save();

        DB::table('queue_exploration')->insert([
            'dominion_id' => $dominion->id,
            'land_type' => 'plain',
            'amount' => 10,
            'hours' => 2,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'home',
            'amount' => 10,
            'hours' => 2,
        ]);

        // Test queue hours 2 -> 1
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 0, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 1])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 1]);

        // Test queue hours 1 -> 0
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 0, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 0])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 0]);

        // Test queues get processed on hour 0
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 10, 'building_home' => 10])
            ->dontSeeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain'])
            ->dontSeeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home']);
    }
}
