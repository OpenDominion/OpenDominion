<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Tests\BaseTestCase;

class RealmFinderServiceTest extends BaseTestCase
{
    use DatabaseMigrations;

    /** @var Round */
    protected $round;

    /** @var RealmFinderService */
    protected $realmFinderService;

    public function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->round = $this->createRound();

        $this->realmFinderService = $this->app->make(RealmFinderService::class);
    }

    public function testFindRandomReturnsVacantRealmBasedOnRaceAlignment()
    {
        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $goodRealm = $this->createRealm($this->round, $goodRace->alignment);

        $evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $evilRealm = $this->createRealm($this->round, $evilRace->alignment);

        $testRealm = $this->realmFinderService->findRandom($this->round, $goodRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($goodRealm->id, $testRealm->id);

        $testRealm = $this->realmFinderService->findRandom($this->round, $evilRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($evilRealm->id, $testRealm->id);
    }

    public function testFindRandomReturnsNullWhenNoValidRealmExists()
    {
        $goodRace = Race::where('alignment', 'good')->firstOrFail();
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this->assertNull($this->realmFinderService->findRandom($this->round, $goodRace));

        $this->createRealm($this->round, $evilRace->alignment);

        $this->assertNull($this->realmFinderService->findRandom($this->round, $goodRace));
    }

    public function testFindRandomReturnsNullIfAllValidRealmsAreFull()
    {
        $goodRace = Race::where('alignment', 'good')->firstOrFail();

        // Create 3 realms full of dominions
        for ($i = 0; $i < 3; $i++) {
            for ($dominionCounter = 0; $dominionCounter < 12; $dominionCounter++) {
                $this->createDominion($this->createUser(), $this->round, $goodRace);
            }
        }

        $this->assertEquals(3, Realm::count());

        $this->assertNull($this->realmFinderService->findRandom($this->round, $goodRace));
    }
}
