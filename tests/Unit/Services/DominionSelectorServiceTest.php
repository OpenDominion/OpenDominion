<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Exception;
use Laravel\BrowserKitTesting\DatabaseMigrations;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\DominionSelectorService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class DominionSelectorServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var DominionSelectorService */
    protected $dominionSelectorService;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($this->user, $this->round);
        $this->dominionSelectorService = $this->app->make(DominionSelectorService::class);
    }

    public function testUserCanSelectADominion()
    {
        $this->assertFalse($this->dominionSelectorService->hasUserSelectedDominion());
        $this->assertNull($this->dominionSelectorService->getUserSelectedDominion());

        $this->dominionSelectorService->selectUserDominion($this->dominion);

        $this->assertTrue($this->dominionSelectorService->hasUserSelectedDominion());
        $this->assertEquals($this->dominion->id, $this->dominionSelectorService->getUserSelectedDominion()->id);
    }

    /**
     * @expectedException Exception
     */
    public function testUserCannotSelectSomeoneElsesDominion()
    {
        $user2 = $this->createUser();
        $dominion2 = $this->createDominion($user2, $this->round);

        $this->dominionSelectorService->selectUserDominion($dominion2);
    }
}
