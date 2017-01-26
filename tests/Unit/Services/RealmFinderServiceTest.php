<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\RealmFinderService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RealmFinderServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $goodRace;

    /** @var RealmFinderService */
    protected $realmFinderService;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->round = $this->createRound();
        $this->goodRace = Race::where('alignment', 'good')->firstOrFail();

        $this->realmFinderService = $this->app->make(RealmFinderService::class);
    }

    public function testFindRandomReturnsVacantRealmBasedOnRaceAlignment()
    {
        $goodRealm = $this->createRealm($this->round, $this->goodRace->alignment);

        $evilRace = Race::where('alignment', 'evil')->firstOrFail();
        $evilRealm = $this->createRealm($this->round, $evilRace->alignment);

        $testRealm = $this->realmFinderService->findRandom($this->round, $this->goodRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($goodRealm->id, $testRealm->id);

        $testRealm = $this->realmFinderService->findRandom($this->round, $evilRace);

        $this->assertNotNull($testRealm);
        $this->assertEquals($evilRealm->id, $testRealm->id);
    }

    public function testFindRandomReturnsNullWhenNoValidRealmExists()
    {
        $evilRace = Race::where('alignment', 'evil')->firstOrFail();

        $this->assertNull($this->realmFinderService->findRandom($this->round, $this->goodRace));

        $this->createRealm($this->round, $evilRace->alignment);

        $this->assertNull($this->realmFinderService->findRandom($this->round, $this->goodRace));
    }

    public function testFindRandomReturnsNullIfAllValidRealmsAreFull()
    {
        // Create 3 realms full of dominions
        for ($i = 0; $i < 3; $i++) {
            for ($dominionCounter = 0; $dominionCounter < 12; $dominionCounter++) {
                $user = $this->createUser(null, ['email' => "test-{$i}-{$dominionCounter}@example.com"]);
                $this->createDominion($user, $this->round, $this->goodRace) ;
            }
        }

        $this->assertEquals(3, Realm::count());

        $this->assertNull($this->realmFinderService->findRandom($this->round, $this->goodRace));
    }
}
