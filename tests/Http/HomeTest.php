<?php

namespace OpenDominion\Tests\Http;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractTestCase;

class HomeTest extends AbstractTestCase
{
    use DatabaseTransactions;

    public function testHomePage()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testHomePageLinksToTheRoundCalendar()
    {
        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee(route('round.calendar'));
    }

    public function testRedirectLoggedInUserWithoutSelectedDominionToDashboard()
    {
        $this->createAndImpersonateUser();

        $response = $this->get('/');

        $response->assertRedirect('/dashboard');
    }

    public function testRedirectLoggedInUserWithSelectedDominionToStatus()
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->createAndSelectDominion($user, $round);

        $response = $this->get('/');

        $response->assertRedirect('/dominion/status');
    }

    public function testUserShouldNotGetRedirectedOnReferredRequests()
    {
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $response = $this->get('/', [
            'HTTP_REFERER' => 'foo',
        ]);

        $response
            ->assertStatus(200)
            ->assertSee('Dashboard');

        $this->selectDominion($dominion);

        $response = $this->get('/', [
            'HTTP_REFERER' => 'foo',
        ]);

        $response
            ->assertStatus(200)
            ->assertSee('Play');
    }

    public function testHomePageShowsCurrentRoundWhenARoundIsInProgress()
    {
        $this->travelPastExistingRounds();
        $this->createRoundWithNumber(1, '-10 days', '+20 days');
        $this->createRoundWithNumber(2, '+30 days', '+70 days');

        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('Round #1')
            ->assertSee('Current Round Rankings')
            ->assertDontSee('Upcoming Rounds')
            ->assertDontSee('Previous Round Rankings');
    }

    public function testHomePageShowsUpcomingRoundsWhenNoRoundIsInProgress()
    {
        $this->travelPastExistingRounds();
        $this->createRoundWithNumber(1, '-60 days', '-10 days');
        $this->createRoundWithNumber(2, '+30 days', '+70 days');

        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('Upcoming Rounds')
            ->assertSee('Previous Round Rankings')
            ->assertDontSee('Round #1');
    }

    public function testHomePageShowsInactiveWhenNoCurrentOrUpcomingRounds()
    {
        $this->travelPastExistingRounds();
        $this->createRoundWithNumber(1, '-60 days', '-10 days');

        $response = $this->get('/');

        $response
            ->assertStatus(200)
            ->assertSee('There is no ongoing round.')
            ->assertSee('Previous Round Rankings')
            ->assertDontSee('Upcoming Rounds');
    }

    /**
     * Freezes time beyond any round already present in the database, so that only
     * the rounds created by the test itself are current or upcoming.
     */
    protected function travelPastExistingRounds(): void
    {
        Carbon::setTestNow(Carbon::create(2200, 1, 1, 0, 0, 0));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function createRoundWithNumber(int $number, string $startDate, string $endDate): Round
    {
        return Round::create([
            'round_league_id' => 1,
            'number' => $number,
            'name' => "Testing Round {$number}",
            'start_date' => new Carbon($startDate),
            'end_date' => new Carbon($endDate),
            'pack_size' => 6,
        ]);
    }
}
