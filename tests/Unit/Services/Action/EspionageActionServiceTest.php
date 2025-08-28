<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class EspionageActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var EspionageActionService */
    protected $espionageActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('-3 days midnight');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Halfling')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->land_plain = 10000;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Nomad')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->land_plain = 10000;

        $this->espionageActionService = $this->app->make(EspionageActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testPerformOperation_SameSpa_LoseQuarterPercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 5000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4988, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchLowerSpa_LoseMaxOnePercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 50000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4950, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchHigherSpa_LoseQuarterPercent()
    {
        // Arrange
        $this->dominion->military_spies = 5000;
        $this->target->military_spies = 500;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(4988, $this->dominion->military_spies);
    }

    public function testPerformOperation_SameSpa_LoseMilitary()
    {
        // Arrange
        $this->dominion->military_unit3 = 20000;
        $this->target->military_spies = 3000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(19997, $this->dominion->military_unit3);
    }
}
