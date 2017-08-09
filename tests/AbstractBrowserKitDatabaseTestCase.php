<?php

namespace OpenDominion\Tests;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;

abstract class AbstractBrowserKitDatabaseTestCase extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected $initialized = false;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Realm */
    protected $realm;

    /** @var Dominion */
    protected $dominion;

    protected function setUp()
    {
        parent::setUp();

        $this->initialize();
    }

    protected function initialize()
    {
        if ($this->initialized) {
            return;
        }

        $this->artisan('migrate');
        $this->seed(CoreDataSeeder::class);

        $this->user = $this->createUser();
        $this->round = $this->createRound();
        $this->realm = $this->createRealm($this->round);
        $this->dominion = $this->createDominion($this->user, $this->round);

        $this->initialized = true;
    }
}
