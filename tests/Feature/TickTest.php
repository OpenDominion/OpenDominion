<?php

namespace OpenDominion\Tests\Feature;

use Artisan;
use CoreDataSeeder;
use DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class TickTest extends AbstractBrowserKitTestCase
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
            'hours' => 3,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'home',
            'amount' => 10,
            'hours' => 3,
        ]);

        // Test queue hours 3 -> 2
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 0, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 2])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 2]);

        // Test queue hours 2 -> 1
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 0, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 1])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 1]);

        // Test queues get processed on hour 0
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 10, 'building_home' => 10])
            ->dontSeeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain'])
            ->dontSeeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home']);
    }

    public function testQueueShouldntTickLockedDominions()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound('-2 days', '-1 days');
        $dominion = $this->createDominion($user, $round);

        $dominion->fill([
            'peasants' => 0,
            'morale' => 0,
            'spy_strength' => 0,
            'wizard_strength' => 0,
            'resource_platinum' => 0,
            'building_home' => 100,
            'building_alchemy' => 100,
        ])->save();

        $this->assertTrue($dominion->isLocked());

        DB::table('queue_exploration')->insert([
            'dominion_id' => $dominion->id,
            'land_type' => 'plain',
            'amount' => 10,
            'hours' => 3,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'home',
            'amount' => 10,
            'hours' => 3,
        ]);

        Artisan::call('game:tick');

        $this
            ->seeInDatabase('dominions', [
                'id' => $dominion->id,
                'peasants' => 0,
                'morale' => 0,
                'spy_strength' => 0,
                'wizard_strength' => 0,
                'resource_platinum' => 0,
            ])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 3])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 3]);
    }

    public function testResourcesGetGeneratedOnTheSameHourThatBuildingsComeIn()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->resource_gems = 0;
        $dominion->resource_mana = 0;
        $dominion->save();

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'diamond_mine',
            'amount' => 20,
            'hours' => 1,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'tower',
            'amount' => 20,
            'hours' => 1,
        ]);

        Artisan::call('game:tick');

        $this->seeInDatabase('dominions', [
            'resource_gems' => 300,
            'resource_mana' => 500,
        ]);
    }
}
