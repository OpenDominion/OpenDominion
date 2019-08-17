<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\EspionageActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class EspionageActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    /** @var EspionageActionService */
    protected $espionageActionService;

    /** @var Round */
    protected $round;

    /** @var Dominion */
    protected $dominion;

    /** @var Dominion */
    protected $target;

    public function setUp()
    {
        parent::setUp();

        $this->seedDatabase();

        $user = $this->createAndImpersonateUser();
        $this->round = $this->createRound('last week');

        $this->dominion = $this->createDominion($user, $this->round);
        $this->dominion->created_at = Carbon::now()->addHours(-80);

        $targetUser = $this->createUser();
        $this->target = $this->createDominion($targetUser, $this->round, Race::where('name', 'Nomad')->firstOrFail());
        $this->target->created_at = Carbon::now()->addHours(-80);

        $this->espionageActionService = $this->app->make(EspionageActionService::class);

        global $mockRandomChance;
        $mockRandomChance = true;
    }

    public function testPerformOperation_SameSpa_LoseFivePermille()
    {
        // Arrange
        $this->dominion->military_spies = 10000;
        $this->target->military_spies = 10000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(9950, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchLowerSpa_LoseMaxTwoPercent()
    {
        // Arrange
        $this->dominion->military_spies = 10000;
        $this->target->military_spies = 10000000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(9800, $this->dominion->military_spies);
    }

    public function testPerformOperation_MuchHigherSpa_LoseHalfAPercent()
    {
        // Arrange
        $this->dominion->military_spies = 10000;
        $this->target->military_spies = 100;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals(9950, $this->dominion->military_spies);
    }
}
