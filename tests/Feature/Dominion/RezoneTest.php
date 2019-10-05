<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class RezoneTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var Dominion */
    protected $dominion;

    public function setUp()
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $round = $this->createRound();
        $this->dominion = $this->createAndSelectDominion($user, $round);
    }

    /**
     * Baseline.
     */
    public function testOpeningRezonePage()
    {
        $this->visitRoute('dominion.rezone')
            ->see('Re-zone Land');
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
            ->see('Your land has been re-zoned')
            ->seeInDatabase('dominions', [
                'id' => $this->dominion->id,
                'land_plain' => 108,
                'land_mountain' => 22,
            ]);
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
            ->see('Re-zoning was not completed due to bad input.')
            ->assertDominionUnchanged();
    }

    /**
     * Test that rezoning fails if you cannot afford it.
     */
    public function testRezoningWithoutEnoughPlatinumFails()
    {
        $this->dominion->resource_platinum = 0;
        $this->dominion->save();

        $this->visitRoute('dominion.rezone')
            ->type('3', 'remove[plain]')
            ->type('3', 'add[mountain]')
            ->press('Re-Zone')
            ->see('One or more errors occurred')
            ->see('You do not have enough platinum to re-zone 3 acres of land.')
            ->assertDominionUnchanged(['resource_platinum' => 0]);
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
            ->see('You do not have enough barren land to re-zone 50 plains')
            ->assertDominionUnchanged();
    }

    /**
     * Helper function to assert the affected properties have not been changed.
     *
     * @param array $attributes
     * @return static
     */
    protected function assertDominionUnchanged(array $attributes = [])
    {
        // todo: pull up
        return $this->seeInDatabase('dominions', array_merge([
            'id' => $this->dominion->id,
            'resource_platinum' => 100000,
            'land_plain' => 110,
            'land_mountain' => 20,
        ], $attributes));
    }
}
