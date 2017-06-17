<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RoundTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testUserSeesNoActiveRoundsWhenNoRoundsAreActive()
    {
        $this->createAndImpersonateUser();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('There are currently no active rounds.');
    }

    public function testUserCanSeeActiveRounds()
    {
        $this->seed(CoreDataSeeder::class);
        $this->createAndImpersonateUser();
        $this->createRound();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'warning'])
            ->see('Testing Round')
            ->see('(Standard league)')
            ->see('Ending in 50 days')
            ->see('Register')
            ->seeInElement('a', 'Register');
    }

    public function testUserCanSeeRoundWhichStartSoon()
    {
        $this->seed(CoreDataSeeder::class);
        $this->createAndImpersonateUser();
        $this->createRound('+3 days', '+53 days');

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'success'])
            ->see('Testing Round')
            ->see('(Standard league)')
            ->see('Starting in 3 days')
            ->seeInElement('a', 'Register');
    }

    public function testUserCanSeeRoundsWhichDontStartSoon()
    {
        $this->seed(CoreDataSeeder::class);
        $this->createAndImpersonateUser();
        $this->createRound('+5 days', '+55 days');

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->seeElement('tr', ['class' => 'danger'])
            ->see('Testing Round')
            ->see('(Standard league)')
            ->see('Starting in 5 days')
            ->dontSeeInElement('a', 'Register');
    }

    public function testUserCanRegisterToARound()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->click('Register')
            ->seePageIs('round/1/register')
            ->see('Register to round 1 (Standard league)')
            ->type('dominionname', 'dominion_name')
            ->select(1, 'race')
            ->select('random', 'realm')
            ->press('Register')
            ->seePageIs('dominion/status')
            ->see('You have successfully registered to round 1 (Standard league)')
            ->seeInDatabase('dominions', [
                'user_id' => $user->id,
                'round_id' => $round->id,
                'race_id' => 1,
                'name' => 'dominionname',
            ])
            ->get('round/1/register')
            ->seeStatusCode(500);
    }

    public function testMultipleUsersCanRegisterToARoundAsAPack()
    {
        $this->markTestIncomplete();
    }

    public function testPacksMustContainUniqueRacesOfSameOrNeutralAlignment()
    {
        $this->markTestIncomplete();
    }

    // todo: round milestones (prot to d3, early to d16, mid to d33, late to d45, end to d50)
    // todo: other round stages, like war/black ops stuff (after 5/7 days)
    // todo: post-round stuff
}
