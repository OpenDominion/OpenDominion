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

    /** @var Realm */
    protected $realm;

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
        $this->realm = $this->createRealm($this->round, $this->race->alignment);

        $this->dominionFactory = $this->app->make(DominionFactory::class);
        $this->packService =  $this->app->make(PackService::class);
        $this->realmFactory = $this->app->make(RealmFactory::class);
    }

    public function testCreate()
    {
        $this->assertEquals(0, Dominion::count());

        $dominion = $this->dominionFactory->create(
            $this->user,
            $this->realm,
            $this->race,
            'Ruler Name',
            'Dominion Name'
        );

        $this->assertEquals(1, Dominion::count());
        $this->assertEquals($dominion->id, Dominion::first()->id);
    }

    public function testCreateReturnsEligibleRealmIfAlreadyFilledWithPack()
    {
        $dominion = $this->createDominion($this->user, $this->round, $this->race);
        $realm = $dominion->realm;

        $this->assertEquals(1, $realm->dominions()->count());

        // Create a new pack
        $this->be($this->user);
        $pack = $this->packService->createPack($dominion, 'pack name', 'pack password', 3);

        $otherUser = $this->createUser();
        // create other dominion with random realm type
        $this->dominionFactory->create($otherUser, $this->round, $this->race, 'random', 'ruler', 'dominion');

        $realm->refresh();

        $this->assertEquals(1, $realm->packs()->count());
        $this->assertEquals(2, $realm->dominions()->count());
    }

    // todo: test realmType / multiple dominions in realm?
}
