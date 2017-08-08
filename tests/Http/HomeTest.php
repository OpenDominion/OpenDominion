<?php

namespace OpenDominion\Tests\Http;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractHttpTestCase;

class HomeTest extends AbstractHttpTestCase
{
    use DatabaseMigrations;

    public function testIndex()
    {
        $this->get('/')
            ->assertStatus(200);
    }

    public function testRedirectLoggedWithoutSelectedDominionToDashboard()
    {
        $user = $this->createAndImpersonateUser();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function testRedirectLoggedWithSelectedDominionToStatus()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/dominion/status');
    }
}
