<?php

namespace OpenDominion\Tests\Http;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RoundTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

//    public function testUserSeesNoActiveRoundsWhenNoRoundsAreActive()
//    {
//        $this->disableActiveRounds();
//        $this->createAndImpersonateUser();
//
//        $this->visit('/dashboard')
//            ->see('Dashboard')
//            ->see('There are currently no active rounds.');
//    }

    public function testUserCanSeeActiveRounds()
    {
        $this->disableActiveRounds();
        $this->createAndImpersonateUser();
        $this->createRound();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'warning'])
            ->see('Testing Round')
            ->see('Register')
            ->seeInElement('a', 'Register');
    }

    public function testUserCanSeeRoundWhichStartSoon()
    {
        $this->disableActiveRounds();
        $this->createAndImpersonateUser();
        $this->createRound('+2 days', '+49 days');

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'success'])
            ->see('Testing Round')
            ->see('Starts in 1 day')
            ->seeInElement('a', 'Register');
    }

    public function testUserCanSeeRoundsWhichDontStartSoon()
    {
        $this->disableActiveRounds();
        $this->createAndImpersonateUser();
        $this->createRound('+5 days', '+52 days');

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'success'])
            ->see('Testing Round')
            ->see('Starts in 4 days');
    }

    public function testUserCanRegisterToARound()
    {
        $this->disableActiveRounds();
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $race = Race::where('key', 'human')->firstOrFail();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->click('Register')
            ->seePageIs("round/{$round->id}/register")
            ->see("Register to round {$round->name} (#{$round->number})")
            ->type('dominionname', 'dominion_name')
            ->type('rulername', 'ruler_name')
            ->select($race->key, 'race')
            ->select('random', 'realm_type')
            ->press('Register')
            ->seePageIs('dominion/status')
            ->see("You have successfully registered to round {$round->number}")
            ->seeInDatabase('dominions', [
                'user_id' => $user->id,
                'round_id' => $round->id,
                'race_id' => $race->id,
                'name' => 'dominionname',
            ])
            ->get("round/{$round->id}/register")
            ->seeStatusCode(302);
    }

    public function testMultipleUsersCanRegisterToARoundAsAPack()
    {
        $this->markTestIncomplete();
    }

    public function testPacksMustContainUniqueRacesOfSameOrNeutralAlignment()
    {
        $this->markTestIncomplete();
    }

    private function disableActiveRounds(): void
    {
        Round::whereNotNull('end_date')->update([
            'end_date' => now()->subDays(1),
        ]);
    }

    // todo: round milestones (prot to d3, early to d16, mid to d33, late to d45, end to d50)
    // todo: other round stages, like war/black ops stuff (after 5/7 days)
    // todo: post-round stuff
}
