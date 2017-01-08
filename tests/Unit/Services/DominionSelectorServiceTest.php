<?php

namespace OpenDominion\Tests\Unit\Services;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\DominionSelectorService;
use OpenDominion\Tests\BaseTestCase;

class DominionSelectorServiceTest extends BaseTestCase
{
    use DatabaseMigrations;

    /** @var User */
    protected $user;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    protected function setUp()
    {
        parent::setUp();

        $this->seed(CoreDataSeeder::class);

        $this->user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->dominion = $this->createDominion($this->user, $this->round);
    }

    public function testUserCanSelectADominion()
    {
        $dominionSelectorService = $this->app->make(DominionSelectorService::class);

        $this->assertFalse($dominionSelectorService->hasUserSelectedDominion());
        $this->assertNull($dominionSelectorService->getUserSelectedDominion());

        $dominionSelectorService->selectUserDominion($this->dominion);

        $this->assertTrue($dominionSelectorService->hasUserSelectedDominion());
        $this->assertEquals($this->dominion->id, $dominionSelectorService->getUserSelectedDominion()->id);
    }

    /**
     * @expectedException \Exception
     */
    public function testUserCannotSelectSomeoneElsesDominion()
    {
        $user2 = $this->createUser();
        $dominion2 = $this->createDominion($user2, $this->round);

        $dominionSelectorService = $this->app->make(DominionSelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion2);
    }
}
