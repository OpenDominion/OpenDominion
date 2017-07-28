<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\Dominion\SelectorService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DominionTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testUserSeesNoActiveDominionsWhenUserDoesntHaveAnyActiveDominions()
    {
        // todo: move to DashboardTest
        $this->createAndImpersonateUser();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('You have no active dominions');
    }

    public function testUserCantPlayYetDuringPreRound()
    {
        // todo: segment this and move this to dominion select, have general
        // dominion page tests for http 200 responses, action requests etc
        // aka acceptance tests
        $this->markTestIncomplete();
    }

    public function testUserCanBeginPlayingOnceRoundStarts()
    {
        $this->markTestIncomplete();
    }

    public function testUserCantSeeStatusPageIfNoDominionIsSelected()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $this->visit('/dominion/status')
            ->seePageIs('/dashboard');
    }

    public function testUserCanSeeStatusPage()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominionSelectorService = app(SelectorService::class);
        // todo: $this->selectDominion
        $dominionSelectorService->selectUserDominion($dominion);

        $this->visit('/dominion/status')
            ->see("The Dominion of {$dominion->name}");
    }
}
