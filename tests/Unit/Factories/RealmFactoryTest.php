<?php

namespace OpenDominion\Tests\Unit\Factories;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Tests\BaseTestCase;

class RealmFactoryTest extends BaseTestCase
{
    use DatabaseMigrations;

    /** @var Round */
    protected $round;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->round = $this->createRound();
    }

    public function testCreate()
    {
        $realmFactory = $this->app->make(RealmFactory::class);

        $this->assertEquals(0, Realm::count());

        $realm = $realmFactory->create($this->round, 'good');

        $this->assertEquals(1, Realm::count());
        $this->assertEquals($realm->id, Realm::first()->id);
    }
}
