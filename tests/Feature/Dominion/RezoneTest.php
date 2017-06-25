<?php

namespace Tests\Feature\Dominion;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\DominionSelectorService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RezoneTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var  \OpenDominion\Models\Dominion */
    protected $originalDominion;
    /** @var  \OpenDominion\Services\DominionSelectorService */
    protected $dominionSelectorService;

    const REZONE_COST = 250;

    public function setUp()
    {
        parent::setUp();
        $this->seed(CoreDataSeeder::class);
        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);
        $dominion->update(['resource_platinum' => 2 * self::REZONE_COST]);
        // @TODO: create dominion with specified amounts of land and resources
        // so that this test doesn't break if the defaults in the factory change.
        $this->dominionSelectorService = app(DominionSelectorService::class);
        $this->dominionSelectorService->selectUserDominion($dominion);
        $this->originalDominion = clone $this->dominionSelectorService->getUserSelectedDominion();
    }

    /**
     * Baseline.
     */
    public function testOpeningRezonePage()
    {
        $this->visitRoute('dominion.rezone')
            ->see('Re-zone land');
    }

    /**
     * Test a successful rezoning of 2 plains to mountains.
     */
    public function testRezoningPlainToMountain()
    {
        $this->visitRoute('dominion.rezone')
            ->type('2', 'remove[plain]')
            ->type('2', 'add[mountain]')
            ->press('Re-Zone')
            ->see('Your land has been re-zoned');
        $dominion = $this->dominionSelectorService->getUserSelectedDominion();
        $this->assertEquals($this->originalDominion->land_plain - 2, $dominion->land_plain);
        $this->assertEquals($this->originalDominion->land_mountain + 2, $dominion->land_mountain);
        $this->assertEquals($this->originalDominion->resource_platinum - 2 * self::REZONE_COST,
            $dominion->resource_platinum);
    }

    /**
     * Test that trying to add more land than removing fails.
     */
    public function testCreatingLandOutOfNowhereFails()
    {
        $this->visitRoute('dominion.rezone')
            ->type('10', 'add[plain]')
            ->press('Re-Zone')
            ->see('One or more errors occurred')
            ->see('Rezoning must remove and add equal amounts of land.');
        $this->assertDominionUnchanged();
    }

    /**
     * Test that rezoning fails if you cannot afford it.
     */
    public function testRezoningWithoutEnoughPlatinumFails()
    {
        $this->visitRoute('dominion.rezone')
            ->type('3', 'remove[plain]')
            ->type('3', 'add[mountain]')
            ->press('Re-Zone')
            ->see('One or more errors occurred')
            ->see('Not enough platinum.');
        $this->assertDominionUnchanged();
    }

    /**
     * Test that rezoning only works if you have enough barren land.
     */
    public function testRezoningWithoutEnoughBarrenLandFails()
    {
        $this->visitRoute('dominion.rezone')
            ->type('50', 'remove[plain]')
            ->type('50', 'add[mountain]')
            ->press('Re-Zone')
            ->see('One or more errors occurred')
            ->see('Can only rezone 40 plains');
        $this->assertDominionUnchanged();
    }

    /**
     * Helper function to assert the affected properties have not been changed.
     */
    protected function assertDominionUnchanged()
    {
        $dominion = $this->dominionSelectorService->getUserSelectedDominion();
        foreach (['land_plain', 'land_mountain', 'resource_platinum'] as $affectedProperty) {
            $this->assertEquals($this->originalDominion->{$affectedProperty}, $dominion->{$affectedProperty},
                'Unexpected change to ' . $affectedProperty);
        }
    }
}
