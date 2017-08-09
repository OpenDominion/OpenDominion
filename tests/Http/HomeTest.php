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

    public function testRedirectLoggedUserWithoutSelectedDominionToDashboard()
    {
        $user = $this->createAndImpersonateUser();

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/dashboard');
    }

    public function testRedirectLoggedUserWithSelectedDominionToStatus()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect('/dominion/status');
    }

    public function testUserShouldNotGetRedirectedOnReferredRequests()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $this->actingAs($user)
            ->get('/', ['HTTP_REFERER' => 'foo'])
            ->assertStatus(200)
            ->assertSee('Dashboard');

        $this->selectDominion($dominion);

        $this->actingAs($user)
            ->get('/', ['HTTP_REFERER' => 'foo'])
            ->assertStatus(200)
            ->assertSee('Play');
    }
}
