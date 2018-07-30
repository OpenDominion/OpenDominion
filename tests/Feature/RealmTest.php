<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RealmTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testNewDominionGetsPlacedInARealmBasedOnRaceAlignment()
    {
        $this->seedDatabase();

        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this
            ->dontSeeInDatabase('realms', ['alignment' => 'good'])
            ->dontSeeInDatabase('realms', ['alignment' => 'evil']);

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

    public function testRealmsCantContainMoreThan12Dominions()
    {
        $this->seedDatabase();

        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        $this->dontSeeInDatabase('realms', ['alignment' => 'good']);

        // Create 13 Dominions, where the first 12 should be in realm 1 and the 13th in realm 2
        for ($i = 0; $i < 13; $i++) {
            $user = $this->createUser();
            $this->createDominion($user, $round, $goodRace);
        }

        $this
            ->seeInDatabase('realms', ['id' => 1, 'alignment' => 'good'])
            ->seeInDatabase('realms', ['id' => 2, 'alignment' => 'good'])
            ->seeInDatabase('dominions', [ 'id' => 12, 'realm_id' => 1 ])
            ->seeInDatabase('dominions', [ 'id' => 13, 'realm_id' => 2 ]);
    }

    public function testDominionsInAPackGetPlacedInTheSameRealm()
    {
        $this->markTestIncomplete();
    }
}
