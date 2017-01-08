<?php

namespace OpenDominion\Tests\Feature;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\DominionSelectorService;
use OpenDominion\Tests\BaseTestCase;

class DominionTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testUserSeesNoActiveDominionsWhenUserDoesntHaveAnyActiveDominions()
    {
        $this->createAndImpersonateUser();

        $this->visit('/dashboard')
            ->see('Dashboard')
            ->see('You have no active dominions');
    }

    public function testUserCantPlayYetDuringPreRound()
    {
        $this->markTestIncomplete();
    }

    public function testUserCanBeginPlayingOnceRoundStarts()
    {
        $this->markTestIncomplete();
    }

    public function testUserCanSelectADominion()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        $this->assertFalse($dominionSelectorService->hasUserSelectedDominion());
        $this->assertNull($dominionSelectorService->getUserSelectedDominion());

        $dominionSelectorService->selectUserDominion($dominion);

        $this->assertTrue($dominionSelectorService->hasUserSelectedDominion());
        $this->assertEquals($dominion->id, $dominionSelectorService->getUserSelectedDominion()->id);
    }

    /**
     * @expectedException \Exception
     */
    public function testUserCannotSelectSomeoneElsesDominion()
    {
        $this->seed(CoreDataSeeder::class);
        $round = $this->createRound();
        $user1 = $this->createAndImpersonateUser();
        $dominion1 = $this->createDominion($user1, $round);
        $user2 = $this->createUser();
        $dominion2 = $this->createDominion($user2, $round);
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion2);
    }

    public function testUserCantSeeStatusPageIfNoDominionIsSelected()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $this->visit('dominion/status')
            ->seePageIs('/dashboard');
    }

    public function testUserCanSeeStatusPage()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominionSelectorService = app()->make(DominionSelectorService::class);

        $dominionSelectorService->selectUserDominion($dominion);

        $this->visit('dominion/status')
            ->see("The Dominion of {$dominion->name}");
    }
}
