<?php

namespace OpenDominion\Tests\Feature;

use OpenDominion\Models\Race;
use OpenDominion\Tests\AbstractBrowserKitDatabaseTestCase;

class RealmTest extends AbstractBrowserKitDatabaseTestCase
{
    public function testNewDominionGetsPlacedInARealmBasedOnRaceAlignment()
    {
        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $userWithGoodDominion = $this->createUser();
        $goodDominion = $this->createDominion($userWithGoodDominion, $this->round, $goodRace);

        $anotherUserWithGoodDominion = $this->createUser();
        $anotherGoodDominion = $this->createDominion($anotherUserWithGoodDominion, $this->round, $goodRace);

        $userWithEvilDominion = $this->createUser();
        $evilDominion = $this->createDominion($userWithEvilDominion, $this->round, $evilRace);

        $anotherUserWithEvilDominion = $this->createUser();
        $anotherEvilDominion = $this->createDominion($anotherUserWithEvilDominion, $this->round, $evilRace);

        $goodRealm = $goodDominion->realm;
        $evilRealm = $evilDominion->realm;

        $this
            ->seeInDatabase('realms', ['id' => $goodRealm->id, 'alignment' => 'good'])
            ->seeInDatabase('realms', ['id' => $evilRealm->id, 'alignment' => 'evil'])
            ->seeInDatabase('dominions', ['id' => $goodDominion->id, 'realm_id' => $goodRealm->id])
            ->seeInDatabase('dominions', ['id' => $anotherGoodDominion->id, 'realm_id' => $goodRealm->id])
            ->seeInDatabase('dominions', ['id' => $evilDominion->id, 'realm_id' => $evilRealm->id])
            ->seeInDatabase('dominions', ['id' => $anotherEvilDominion->id, 'realm_id' => $evilRealm->id]);
    }

    public function testRealmsCantContainMoreThan12Dominions()
    {
        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        // Create 13 Dominions, where the first 11 should be in realm 1 and the last 2 in realm 2
        for ($i = 0; $i < 13; $i++) {
            $user = $this->createUser();
            $this->createDominion($user, $this->round, $goodRace);
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
