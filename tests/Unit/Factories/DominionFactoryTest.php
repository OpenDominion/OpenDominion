<?php

namespace OpenDominion\Tests\Unit\Factories;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\PackService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DominionFactoryTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Race */
    protected $race;

    /** @var DominionFactory */
    protected $dominionFactory;

    /** @var PackService */
    protected $packService;

    /** @var RealmFactory */
    protected $realmFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->seedDatabase();

        $this->user = $this->createUser();
        $this->round = $this->createRound();
        $this->race = Race::firstOrFail();

        $this->dominionFactory = $this->app->make(DominionFactory::class);
        $this->packService =  $this->app->make(PackService::class);
        $this->realmFactory = $this->app->make(RealmFactory::class);
    }

    public function testCreate()
    {
        $this->assertEquals(0, Realm::count());
        $this->assertEquals(0, Dominion::count());

        $dominion = $this->dominionFactory->create($this->user, $this->round, $this->race, 'random', 'Ruler Name', 'Dominion Name', null);

        $this->assertEquals(1, Realm::count());
        $this->assertEquals(1, Dominion::count());
        $this->assertEquals($dominion->id, Dominion::first()->id);
    }

    public function testCreateReturnsEligibleRealmIfAlreadyFilledWithPack()
    {
//        $existingRealm = $this->realmFactory->create($this->round, 'good');

        $otherUser = $this->createUser();
        $otherDominion = $this->dominionFactory->create($otherUser, $this->round, $this->race, 'random', 'ruler', 'dominion');

        $realm = $otherDominion->realm;

        $this->assertEquals(1, $realm->dominions()->count());
        $this->assertEquals(0, $realm->has_pack);
        $this->assertEquals(0, $realm->reserved_slots);

        // Create a new pack
        $this->be($this->user);
        $pack = $this->packService->getOrCreatePack($this->round, $this->race, 'pack name', 'pack password', 3, true);

        $dominion = $this->dominionFactory->create($this->user, $this->round, $this->race, 'join_pack', 'ruler 2', 'dominion 2', $pack);
        $realm->refresh();

        $this->assertEquals(2, $realm->dominions()->count());
        $this->assertEquals(1, $realm->has_pack);
        $this->assertEquals(5, $realm->reserved_slots);
    }

    // todo: test realmType / multiple dominions in realm?
}
