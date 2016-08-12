<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use DateTime;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;
use OpenDominion\Tests\BaseTestCase;

class RoundTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testNoMoreThanOneRoundCanBeActiveInALeague()
    {
        $this->markTestIncomplete();
    }

    public function testUserSeesNoActiveDominionsWhenUserDoesntHaveAnyActiveDominions()
    {
        $this->createAndImpersonateUser();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('You have no active dominions');
    }

    public function testUserSeesNoActiveRoundsWhenNoRoundsAreActive()
    {
        $this->createAndImpersonateUser();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('There are currently no active rounds.');
    }

    public function testUserCanSeeListOfActiveRounds()
    {
        $this->seed(CoreDataSeeder::class);
        $this->createAndImpersonateUser();

        Round::create([
            'round_league_id' => RoundLeague::where('key', 'standard')->firstOrFail()->id,
            'number' => 1,
            'name' => 'Testing Round',
            'start_date' => new DateTime('today midnight'),
            'end_date' => new DateTime('+50 days midnight'),
        ]);

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('Testing Round')
            ->see('(Standard League)')
            ->see('Started!')
            ->see('50 days');
    }

    public function testUserCanRegisterToASingleRoundInALeague()
    {
        $this->markTestIncomplete();

        // create and be user
        // create round league
        // create round
        // register to round
        // assert dominion entity gets created, placed in a realm (or on round start?) etc
    }

    public function testMultipleUsersCanRegisterToARoundAsAPack()
    {
        $this->markTestIncomplete();
    }

    public function testPacksMustContainUniqueRacesOfSameOrNeutralAlignment()
    {
        $this->markTestIncomplete();
    }

    public function testUserCantPlayYetDuringPreRound()
    {
        $this->markTestIncomplete();
    }

    public function testUserCanBeginPlayingOnceRoundStarts()
    {
        $this->markTestIncomplete();
    }

    // todo: round milestones (prot to d3, early to d16, mid to d33, late to d45, end to d50)
    // todo: post-round stuff
}
