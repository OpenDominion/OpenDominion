<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\Actions\TechCalculator;
use OpenDominion\Models\DominionTech;
use OpenDominion\Models\Tech;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class TechUnlockCountTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var \OpenDominion\Models\User */
    protected $user;

    /** @var \OpenDominion\Models\Round */
    protected $round;

    /** @var \OpenDominion\Models\Dominion */
    protected $dominion;

    /** @var TechCalculator */
    protected $techCalculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createAndImpersonateUser();
        $this->round = $this->createRound();
        $this->round->update(['tech_version' => 2]);
        $this->dominion = $this->createAndSelectDominion($this->user, $this->round);

        $this->techCalculator = app(TechCalculator::class);
    }

    /**
     * Give the dominion the specified amount of research points.
     */
    protected function setResearchPoints(int $amount): void
    {
        $this->dominion->resource_tech = $amount;
        $this->dominion->save();
    }

    /**
     * Permanently unlock every tech of the round's tech version.
     */
    protected function unlockAllTechs(): void
    {
        Tech::where('version', $this->round->tech_version)->get()->each(function ($tech) {
            DominionTech::create([
                'dominion_id' => $this->dominion->id,
                'tech_id' => $tech->id,
            ]);
        });

        $this->dominion->load('techs');
    }

    public function testUnlockableTechCountIsZeroWithoutEnoughResearchPoints()
    {
        $this->setResearchPoints($this->techCalculator->getTechCost($this->dominion) - 1);

        $this->assertEquals(0, $this->techCalculator->getUnlockableTechCount($this->dominion));
    }

    public function testUnlockableTechCountIsBasedOnResearchPoints()
    {
        $this->setResearchPoints(3 * $this->techCalculator->getTechCost($this->dominion));

        $availableTechCount = $this->techCalculator->getAvailableTechCount($this->dominion);
        $this->assertGreaterThan(0, $availableTechCount);

        $this->assertEquals(min(3, $availableTechCount), $this->techCalculator->getUnlockableTechCount($this->dominion));
    }

    public function testUnlockableTechCountIsZeroWhenFullyTeched()
    {
        $this->unlockAllTechs();
        $this->setResearchPoints(10 * $this->techCalculator->getTechCost($this->dominion));

        $this->assertEquals(0, $this->techCalculator->getAvailableTechCount($this->dominion));
        $this->assertEquals(0, $this->techCalculator->getUnlockableTechCount($this->dominion));
    }

    public function testUnlockableTechCountIsCappedByRemainingTechs()
    {
        $this->unlockAllTechs();

        // Re-open a single tech for research
        $lastTech = Tech::where('version', $this->round->tech_version)->get()->last();
        DominionTech::where('dominion_id', $this->dominion->id)
            ->where('tech_id', $lastTech->id)
            ->delete();
        $this->dominion->load('techs');

        $this->setResearchPoints(10 * $this->techCalculator->getTechCost($this->dominion));

        $this->assertEquals(1, $this->techCalculator->getAvailableTechCount($this->dominion));
        $this->assertEquals(1, $this->techCalculator->getUnlockableTechCount($this->dominion));
    }
}
