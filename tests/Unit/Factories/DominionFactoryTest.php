<?php

namespace OpenDominion\Tests\Unit\Factories;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
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

    protected function setUp()
    {
        parent::setUp();

        $this->seedDatabase();

        $this->user = $this->createUser();
        $this->round = $this->createRound();
        $this->race = Race::firstOrFail();

        $this->dominionFactory = $this->app->make(DominionFactory::class);
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

    // todo: test realmType / multiple dominions in realm?
}
