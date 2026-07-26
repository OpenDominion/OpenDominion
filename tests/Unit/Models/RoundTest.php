<?php

namespace OpenDominion\Tests\Unit\Models;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RoundTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testRegistrationClosedWhenStartMoreThanEightDaysAway()
    {
        $round = $this->createRound('+10 days', '+57 days');

        $this->assertFalse($round->registrationOpen());
    }

    public function testRegistrationOpenAtExactlyEightDaysBeforeStart()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');
        $round = $this->createRound('+8 days', '+55 days');

        $this->assertTrue($round->registrationOpen());
        Carbon::setTestNow();
    }

    public function testRegistrationOpenBetweenGateAndStart()
    {
        $round = $this->createRound('+3 days', '+50 days');

        $this->assertTrue($round->registrationOpen());
    }

    public function testRegistrationOpenAfterStartWhileActive()
    {
        $round = $this->createRound('-1 day', '+46 days');

        $this->assertTrue($round->registrationOpen());
    }

    public function testRegistrationClosedAfterRoundEnds()
    {
        $round = $this->createRound('-60 days', '-1 day');

        $this->assertFalse($round->registrationOpen());
    }

    public function testUpcomingScopeExcludesActiveAndEndedRounds()
    {
        $ended = $this->createRound('-60 days', '-10 days');
        $active = $this->createRound('-5 days', '+42 days');
        $upcoming = $this->createRound('+5 days', '+52 days');

        $upcomingIds = Round::upcoming()->pluck('id')->all();

        $this->assertContains($upcoming->id, $upcomingIds);
        $this->assertNotContains($ended->id, $upcomingIds);
        $this->assertNotContains($active->id, $upcomingIds);
    }
}
