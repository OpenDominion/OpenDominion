<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Tests\BaseTestCase;

class RealmFinderServiceTest extends BaseTestCase
{
    use DatabaseMigrations;

    protected $round;

    public function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);
        $this->round = $this->createRound();
    }

    public function testFindRandomReturnsVacantRealmBasedOnRaceAlignment()
    {
        $realmFinderService = $this->app->make(RealmFinderService::class);

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $goodRealm = $this->createRealm($this->round, $goodRace->alignment);

        $evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $evilRealm = $this->createRealm($this->round, $evilRace->alignment);

        $testRealm = $realmFinderService->findRandom($this->round, $goodRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($goodRealm->id, $testRealm->id);

        $testRealm = $realmFinderService->findRandom($this->round, $evilRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($evilRealm->id, $testRealm->id);
    }

    public function testFindRandomReturnsNullWhenNoValidRealmExists()
    {
        $realmFinderService = $this->app->make(RealmFinderService::class);

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this->assertNull($realmFinderService->findRandom($this->round, $goodRace));

        $this->createRealm($this->round, $evilRace->alignment);

        $this->assertNull($realmFinderService->findRandom($this->round, $goodRace));
    }

    public function testFindRandomReturnsNullIfAllValidRealmsAreFull()
    {
        $realmFinderService = $this->app->make(RealmFinderService::class);

        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        // Create 3 realms full of dominions
        for ($i = 0; $i < 3; $i++) {
            for ($dominionCounter = 0; $dominionCounter < 12; $dominionCounter++) {
                $this->createDominion($this->createUser(), $this->round, $goodRace);
            }
        }

        $this->assertEquals(3, Realm::count());

        $this->assertNull($realmFinderService->findRandom($this->round, $goodRace));
    }
}
