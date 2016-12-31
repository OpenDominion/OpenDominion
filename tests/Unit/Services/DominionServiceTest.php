<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery as m;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Services\DominionService;
use OpenDominion\Tests\BaseTestCase;

class DominionServiceTest extends BaseTestCase
{
    use DatabaseMigrations;

    protected $user;

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
        $dominionService = $this->app->make(DominionService::class);

        $this->assertEquals(0, Realm::count());
        $this->assertEquals(0, Dominion::count());

        $dominion = $dominionService->create($this->user, $this->round, $race, 'random', 'Dummy');

        $this->assertEquals(1, Realm::count());
        $this->assertEquals(1, Dominion::count());
        $this->assertEquals($dominion->id, Dominion::first()->id);
    }

    public function testCreateUpdatesDominionNetworth()
    {
        $race = Race::firstOrFail();
        $dominionService = $this->app->make(DominionService::class);

        $dominion = $dominionService->create($this->user, $this->round, $race, 'random', 'Dummy');

        $this->assertEquals(1000, $dominion->networth);
    }
}
