<?php

namespace OpenDominion\Tests\Unit\Services\Action;

use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\Actions\InvadeActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class InvadeActionServiceTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    /** @var InvadeActionService */
    protected $invadeActionService;

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
        $this->round = $this->createRound('last week');

        $this->dominion = $this->createDominionWithLegacyStats($user, $this->round, Race::where('name', 'Merfolk')->firstOrFail());
        $this->dominion->protection_ticks_remaining = 0;
        $this->dominion->land_plain = 2850;

        $targetUser = $this->createUser();
        $this->target = $this->createDominionWithLegacyStats($targetUser, $this->round, Race::where('name', 'Lycanthrope')->firstOrFail());
        $this->target->protection_ticks_remaining = 0;
        $this->target->land_plain = 2850;

        $this->invadeActionService = $this->app->make(InvadeActionService::class);

        global $mockRandomChance;
        $mockRandomChance = false;
    }

    public function testInvadeSucceeds()
    {
        // Arrange
        $this->dominion->military_unit3 = 5000;
        $this->dominion->military_unit4 = 7500; // 30000 raw OP
        $this->target->military_draftees = 0;
        $this->target->military_unit2 = 10000; // 30000 raw DP
        $this->target->building_guard_tower = 30; // 1.6% DP mods (+5% racial)

        // Act
        $units = [
            4 => 7269 // 29076 raw OP +10% OP mods (prestige + racial)
        ];
        $this->invadeActionService->invade($this->dominion, $this->target, $units, false);

        // Get results
        $resultProperty = new \ReflectionProperty($this->invadeActionService, 'invasionResult');
        $resultProperty->setAccessible(true);
        $invasionResult = $resultProperty->getValue($this->invadeActionService);

        // Assert
        $this->assertEquals(true, $invasionResult['result']['success']);
        $this->assertEquals(618, $invasionResult['attacker']['unitsLost'][4]);
        $this->assertEquals(-13, $invasionResult['defender']['prestigeChange']);
        $this->assertEquals(231, $this->dominion->military_unit4);
        $this->assertEquals(9640, $this->target->military_unit2);
        $this->assertEquals(2677, $this->target->land_plain);
    }

    public function testInvadeFails()
    {
        // Arrange
        $this->dominion->military_unit3 = 5000;
        $this->dominion->military_unit4 = 7500; // 30000 raw OP
        $this->target->military_draftees = 0;
        $this->target->military_unit2 = 10000; // 30000 raw DP
        $this->target->building_guard_tower = 30; // 1.6% DP mods (+5% racial)

        // Act
        $units = [
            4 => 7268 // 29072 raw OP +10% OP mods (prestige + racial)
        ];
        $this->invadeActionService->invade($this->dominion, $this->target, $units, false);

        // Get results
        $resultProperty = new \ReflectionProperty($this->invadeActionService, 'invasionResult');
        $resultProperty->setAccessible(true);
        $invasionResult = $resultProperty->getValue($this->invadeActionService);

        // Assert
        $this->assertEquals(false, $invasionResult['result']['success']);
        $this->assertEquals(618, $invasionResult['attacker']['unitsLost'][4]);
        $this->assertEquals(false, isset($invasionResult['defender']['prestigeChange']));
        $this->assertEquals(232, $this->dominion->military_unit4);
        $this->assertEquals(9641, $this->target->military_unit2);
        $this->assertEquals(2850, $this->target->land_plain);
    }

    public function testFasterReturn()
    {
        // Arrange
        $goblin = Race::where('name', 'Goblin')->firstOrFail();
        $this->dominion->race_id = $goblin->id;
        $this->dominion->military_unit2 = 10000;
        $this->dominion->military_unit4 = 5000;
        $this->dominion->resource_boats = 300;

        // Act
        $units = [
            4 => 5000
        ];
        $this->invadeActionService->invade($this->dominion, $this->target, $units, false);
        $returningUnits = $this->dominion->queues()->where('resource', 'military_unit4')->first();

        // Assert
        $this->assertEquals(9, $returningUnits->hours);
    }

    public function testOverpopCasualties()
    {
        // Arrange
        $this->dominion->military_unit3 = 5000;
        $this->dominion->military_unit4 = 7500;
        $this->target->military_draftees = 0;
        $this->target->military_unit2 = 10000;
        $this->target->military_unit4 = 5000;
        $this->target->queues()->create([
            'dominion_id' => $this->target->id,
            'source' => 'invasion',
            'resource' => 'military_unit1',
            'hours' => 12,
            'amount' => 1000
        ]);
        $this->target->queues()->create([
            'dominion_id' => $this->target->id,
            'source' => 'invasion',
            'resource' => 'military_unit4',
            'hours' => 12,
            'amount' => 2000
        ]);

        // Assert
        $populationCalculator = app(PopulationCalculator::class);
        $this->assertEquals(18096, $populationCalculator->getMaxPopulation($this->target));
        $this->assertEquals(18050, $populationCalculator->getPopulationMilitary($this->target));

        // Act
        $units = [4 => 7500];
        $this->invadeActionService->invade($this->dominion, $this->target, $units, false);

        // Get results
        $resultProperty = new \ReflectionProperty($this->invadeActionService, 'invasionResult');
        $resultProperty->setAccessible(true);
        $invasionResult = $resultProperty->getValue($this->invadeActionService);

        // Assert
        $this->assertEquals(17049, $populationCalculator->getMaxPopulation($this->target));
        $this->assertEquals(17673, $populationCalculator->getPopulationMilitary($this->target));
        $this->assertEquals(true, $invasionResult['result']['success']);
        $this->assertEquals(208, $invasionResult['defender']['unitsDeserted'][1]);
        $this->assertEquals(416, $invasionResult['defender']['unitsDeserted'][4]);
    }
}
