<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Tests\AbstractBrowserKitDatabaseTestCase;

class DominionTest extends AbstractBrowserKitDatabaseTestCase
{
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
        $this->be($this->user);

        $this->visit('/dominion/status')
            ->seePageIs('/dashboard');
    }

    public function testUserCanSeeStatusPage()
    {
        $this->be($this->user);
        $this->selectDominion($this->dominion);

        $this->visit('/dominion/status')
            ->see("The Dominion of {$this->dominion->name}");
    }
}
