<?php

namespace OpenDominion\Tests\Feature;

use Carbon\Carbon;
use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Tests\BaseTestCase;

class RoundTest extends BaseTestCase
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
            ->see('(Standard League)')
            ->see('Started!')
            ->see('50 days')
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
            ->see('(Standard League)')
            ->see('In 3 day(s)')// fixme: test sometimes fail on this
            ->see('50 days')
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
            ->see('(Standard League)')
            ->see('In 5 day(s)') // fixme: test sometimes fail on this
            ->see('50 days')
            ->see('In 2 day(s)')
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
            ->see('Register to round 1 (Standard League)')
            ->type('dominionname', 'dominion_name')
            ->select(1, 'race')
            ->select('random', 'realm')
            ->press('Register')
            ->seePageIs('dashboard')
            ->see('You have successfully registered to round 1 (Standard League)')
            ->seeInDatabase('dominions', [
                'user_id' => $user->id,
                'round_id' => $round->id,
                'race_id' => 1,
                'name' => 'dominionname',
            ])
            ->see('dominionname')
            ->seeElement('tr', ['class' => 'info'])
            ->see('Already registered!')
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
    // todo: post-round stuff
}
