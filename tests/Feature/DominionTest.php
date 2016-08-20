<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class DominionTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testUserCanSeeStatusPage()
    {
        $this->seed(\CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $realm = $this->createRealm($round);
        $dominion = $this->createDominion($user, $round, $realm);

        $this->visit('dominion/1/status')
            ->see('Testing Dominion (#1 Testing Round)');
    }
}
