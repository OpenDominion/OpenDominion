<?php

namespace OpenDominion\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RealmFinderServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $goodRace;

    /** @var RealmFinderService */
    protected $realmFinderService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound();
        $this->goodRace = Race::where('alignment', 'good')->firstOrFail();
        $this->user = $this->createUser();

        $this->realmFinderService = $this->app->make(RealmFinderService::class);
    }

    public function testFindRandomReturnsVacantRealmBasedOnRaceAlignment()
    {
        $goodRealm = $this->createRealm($this->round, $this->goodRace->alignment);

        $evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $evilRealm = $this->createRealm($this->round, $evilRace->alignment);

        $testRealm = $this->realmFinderService->findRealm($this->round, $this->goodRace, $this->user);

        $this->assertNotNull($testRealm);
        $this->assertEquals($goodRealm->id, $testRealm->id);

        $testRealm = $this->realmFinderService->findRealm($this->round, $evilRace, $this->user);

        $this->assertNotNull($testRealm);
        $this->assertEquals($evilRealm->id, $testRealm->id);
    }

    public function testFindRandomReturnsNullWhenNoValidRealmExists()
    {
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this->assertNull($this->realmFinderService->findRealm($this->round, $this->goodRace, $this->user));

        $this->createRealm($this->round, $evilRace->alignment);

        $this->assertNull($this->realmFinderService->findRealm($this->round, $this->goodRace, $this->user));
    }

    public function testFindRealmReturnsRealmWithClosedPacksSlots()
    {
        $this->realmFinderService->maxPacksPerRealm = null;

        // Dominion slot 1
        $dominion = $this->createDominion($this->user, $this->round, $this->goodRace);
        $realm = $dominion->realm;

        // Dominion slots 2-10
        for ($i = 0; $i < 9; $i++) {
            $this->createDominion($this->createUser(), $this->round, $this->goodRace);
        }

        $this->assertEquals(10, $realm->dominions()->count());
        $this->assertEquals($realm->id, $this->realmFinderService->findRealm($this->round, $this->goodRace, $this->user)->id);

        // Last 2 spots reserved for pack from user in spot 1
        $pack = Pack::create([
            'round_id' => $this->round->id,
            'realm_id' => $realm->id,
            'creator_dominion_id' => $dominion->id,
            'name' => 'test pack name',
            'password' => 'test pack password',
            'size' => 3,
            'closed_at' => now(),
        ]);

        $dominion->pack_id = $pack->id;
        $dominion->save();
        $dominion->refresh();

        $this->assertEquals($realm->id, $this->realmFinderService->findRealm($this->round, $this->goodRace, $this->user)->id);
    }

    public function testFindRealmReturnsNullWhenMaxPacksPerRealmIsReached()
    {
        $this->markTestIncomplete();
    }
}
