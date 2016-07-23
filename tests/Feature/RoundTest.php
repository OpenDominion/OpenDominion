<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class RoundTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testNoMoreThanOneRoundCanBeActiveInALeague()
    {
        $this->markTestIncomplete();
    }

    public function testUserCanSeeListOfActiveRounds()
    {
        $this->markTestIncomplete();

        // create & be user
        // create round league
        // create one or more round
        // visit /rounds?
        // see list of rounds
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
