<?php

namespace OpenDominion\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Race;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RealmTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    public function testNewDominionGetsPlacedInARealmBasedOnRaceAlignment()
    {
        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this
            ->dontSeeInDatabase('realms', ['round_id' => $round->id, 'alignment' => 'good'])
            ->dontSeeInDatabase('realms', ['round_id' => $round->id, 'alignment' => 'evil']);

        $userWithGoodDominion = $this->createUser();
        $goodDominion = $this->createDominion($userWithGoodDominion, $round, $goodRace);
        $goodRealm = $goodDominion->realm;

        $anotherUserWithGoodDominion = $this->createUser();
        $anotherGoodDominion = $this->createDominion($anotherUserWithGoodDominion, $round, $goodRace);
        $this->assertEquals($goodRealm, $anotherGoodDominion->realm);

        $userWithEvilDominion = $this->createUser();
        $evilDominion = $this->createDominion($userWithEvilDominion, $round, $evilRace);
        $evilRealm = $evilDominion->realm;

        $anotherUserWithEvilDominion = $this->createUser();
        $anotherEvilDominion = $this->createDominion($anotherUserWithEvilDominion, $round, $evilRace);
        $this->assertEquals($evilRealm, $anotherEvilDominion->realm);

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
        $round = $this->createRound();
        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        $this->assertEquals(0, $round->realms()->count());

        $firstDominionId = null;
        $firstRealmId = null;
        $lastDominionId = null;
        $lastRealmId = null;

        // Create 13 Dominions, where the first 12 should be in realm #1 and the 13th in realm #2
        for ($i = 0; $i < 13; $i++) {
            $user = $this->createUser();

            $dominion = $this->createDominion($user, $round, $goodRace);

            if ($firstDominionId === null) {
                $firstDominionId = $dominion->id;
                $firstRealmId = $dominion->realm->id;
            }

            $lastDominionId = $dominion->id;
            $lastRealmId = $dominion->realm->id;
        }

        $this->assertEquals(2, $round->realms()->count());
        $this->assertNotEquals($firstRealmId, $lastRealmId);

        $this
            ->seeInDatabase('realms', ['round_id' => $round->id, 'number' => 1, 'alignment' => 'good'])
            ->seeInDatabase('realms', ['round_id' => $round->id, 'number' => 2, 'alignment' => 'good'])
            ->seeInDatabase('dominions', ['id' => $firstDominionId, 'realm_id' => $firstRealmId])
            ->seeInDatabase('dominions', ['id' => $lastDominionId, 'realm_id' => $lastRealmId]);
    }

    public function testDominionsInAPackGetPlacedInTheSameRealm()
    {
        $this->markTestIncomplete();
    }
}
