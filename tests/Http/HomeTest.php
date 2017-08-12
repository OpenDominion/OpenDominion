<?php

namespace OpenDominion\Tests\Http;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class HomeTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testHomePage()
    {
        $this->visitRoute('home')
            ->seeStatusCode(200);
    }

    public function testRedirectLoggedInUserWithoutSelectedDominionToDashboard()
    {
        $this->createAndImpersonateUser();

        $this->visitRoute('home')
            ->seeRouteIs('dashboard');
    }

    public function testRedirectLoggedInUserWithSelectedDominionToStatus()
    {
        $this->seedDatabase();
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $this->visitRoute('home')
            ->seeRouteIs('dominion.status');
    }

    public function testUserShouldNotGetRedirectedOnReferredRequests()
    {
        $this->seedDatabase();
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $route = route('home');

        $this->get($route, ['HTTP_REFERER' => 'foo'])
            ->seeStatusCode(200)
            ->see('Dashboard');

        $this->selectDominion($dominion);

        $this->get($route, ['HTTP_REFERER' => 'foo'])
            ->seeStatusCode(200)
            ->see('Play');
    }
}
