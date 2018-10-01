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
        $this->markTestSkipped('random_chance needs to be refactored, so it can be mocked.');
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
    }

    public function testPerformOperation_SameSpa_LoseOnePercent()
    {
        // Arrange
        $this->dominion->military_spies = 1000;
        $this->target->military_spies = 1000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals($this->dominion->military_spies, 990);
    }

    public function testPerformOperation_MuchLowerSpa_LoseMaxOneAndAHalfPercent()
    {
        // Arrange
        $this->dominion->military_spies = 1000;
        $this->target->military_spies = 100000;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals($this->dominion->military_spies, 985);
    }

    public function testPerformOperation_MuchHigherSpa_LoseMaxAHalfPercent()
    {
        // Arrange
        $this->dominion->military_spies = 1000;
        $this->target->military_spies = 1;

        // Act
        $this->espionageActionService->performOperation($this->dominion, 'barracks_spy', $this->target);

        // Assert
        $this->assertEquals($this->dominion->military_spies, 995);
    }
}
