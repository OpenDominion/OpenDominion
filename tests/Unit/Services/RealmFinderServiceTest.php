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

    public function testRealmsGetCreated()
    {
        // todo: move to setUp()?
        $this->seed(CoreDataSeeder::class);
        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $realmFinderService = $this->app->make(RealmFinderService::class);

        // Create realms based on alignment

        $goodRealm = $realmFinderService->findRandom($round, $goodRace);
        $this->assertEquals(1, Realm::count());

        $evilRealm = $realmFinderService->findRandom($round, $evilRace);
        $this->assertEquals(2, Realm::count());

        // Don't create a realm if there's already a vacant one

        $anotherGoodRealm = $realmFinderService->findRandom($round, $goodRace);
        $this->assertEquals(2, Realm::count());
        $this->assertEquals($goodRealm->id, $anotherGoodRealm->id);
    }

    public function testNewRealmGetsCreatedIfOneIsFull()
    {
        $this->seed(CoreDataSeeder::class);
        $round = $this->createRound();

        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        $realmFinderService = $this->app->make(RealmFinderService::class);

        for ($i = 0; $i < 12; $i++) {
            $this->createDominion($this->createUser(), $round, $goodRace);
        }

        $this->assertEquals(1, Realm::count());

        $realmFinderService->findRandom($round, $goodRace);

        $this->assertEquals(2, Realm::count());
    }
}
