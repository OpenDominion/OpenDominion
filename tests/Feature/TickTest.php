<?php

namespace OpenDominion\Tests\Feature;

use Artisan;
use CoreDataSeeder;
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
        $this->seeInDatabase('dominions', [ 'id' => $dominion->id, 'morale' => 70]);

        // Test +3 morale above 70
        Artisan::call('game:tick');
        $this->seeInDatabase('dominions', [ 'id' => $dominion->id, 'morale' => 73]);
    }
}
