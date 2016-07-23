<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Tests\BaseTestCase;

class RealmTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testNewDominionGetsPlacedInARealmBasedOnRaceAlignment()
    {
        $this->markTestIncomplete();
    }

    public function testRealmsCantContainMoreThan15Dominions()
    {
        $this->markTestIncomplete();
    }

    public function testDominionsInAPackGetPlacedInTheSameRealm()
    {
        $this->markTestIncomplete();
    }
}
