<?php

namespace OpenDominion\Tests\Unit\Factories;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Tests\BaseTestCase;

class DominionFactoryTest extends BaseTestCase
{
    use DatabaseMigrations;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->user = $this->createUser();
        $this->round = $this->createRound();
    }

    public function testCreate()
    {
        $race = Race::firstOrFail();
        $dominionFactory = $this->app->make(DominionFactory::class);

        $this->assertEquals(0, Realm::count());
        $this->assertEquals(0, Dominion::count());

        $dominion = $dominionFactory->create($this->user, $this->round, $race, 'random', 'Dummy');

        $this->assertEquals(1, Realm::count());
        $this->assertEquals(1, Dominion::count());
        $this->assertEquals($dominion->id, Dominion::first()->id);
    }

    public function testCreateUpdatesDominionNetworth()
    {
        $race = Race::firstOrFail();
        $dominionFactory = $this->app->make(DominionFactory::class);

        $dominion = $dominionFactory->create($this->user, $this->round, $race, 'random', 'Dummy');

        $this->assertEquals(1000, $dominion->networth);
    }
}
