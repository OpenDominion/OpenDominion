<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Tests\BaseTestCase;

class RealmTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testNewDominionGetsPlacedInARealmBasedOnRaceAlignment()
    {
        $this->seed(CoreDataSeeder::class);

        $round = $this->createRound();

        $this
            ->dontSeeInDatabase('realms', ['alignment' => 'good'])
            ->dontSeeInDatabase('realms', ['alignment' => 'evil']);

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $userWithGoodDominion = $this->createUser();
        $goodDominion = $this->createDominion($userWithGoodDominion, $round, $goodRace);

        $anotherUserWithGoodDominion = $this->createUser();
        $anotherGoodDominion = $this->createDominion($anotherUserWithGoodDominion, $round, $goodRace);

        $userWithEvilDominion = $this->createUser();
        $evilDominion = $this->createDominion($userWithEvilDominion, $round, $evilRace);

        $anotherUserWithEvilDominion = $this->createUser();
        $anotherEvilDominion = $this->createDominion($anotherUserWithEvilDominion, $round, $evilRace);

        $this
            ->seeInDatabase('realms', ['id' => 1, 'alignment' => 'good'])
            ->seeInDatabase('realms', ['id' => 2, 'alignment' => 'evil'])
            ->seeInDatabase('dominions', ['id' => $goodDominion->id, 'realm_id' => 1])
            ->seeInDatabase('dominions', ['id' => $anotherGoodDominion->id, 'realm_id' => 1])
            ->seeInDatabase('dominions', ['id' => $evilDominion->id, 'realm_id' => 2])
            ->seeInDatabase('dominions', ['id' => $anotherEvilDominion->id, 'realm_id' => 2]);
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
