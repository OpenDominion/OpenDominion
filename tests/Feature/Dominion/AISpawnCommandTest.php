<?php

namespace OpenDominion\Tests\Feature\Dominion;

use Artisan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;
use RuntimeException;

class AISpawnCommandTest extends AbstractBrowserKitTestCase
{
    use DatabaseTransactions;

    protected Round $round;

    protected Realm $graveyard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->round = $this->createRound('-3 days');
        $this->round->update(['mixed_alignment' => true]);

        $this->graveyard = Realm::create([
            'round_id' => $this->round->id,
            'alignment' => 'neutral',
            'number' => 0,
            'name' => 'The Graveyard',
        ]);
    }

    public function testSpawnsExplorersByDefault(): void
    {
        Artisan::call('game:ai:spawn', ['--race' => 'Human', '--count' => 2, '--round' => $this->round->id, '--land' => 500]);

        $bots = $this->graveyard->dominions()->get();
        $this->assertCount(2, $bots);
        foreach ($bots as $bot) {
            $this->assertNull($bot->user_id);
            $this->assertTrue((bool) $bot->ai_enabled);
            $this->assertEquals('Human', $bot->race->name);
            $this->assertArrayNotHasKey('strategy', $bot->ai_config);
            $this->assertArrayHasKey('build', $bot->ai_config);
        }
    }

    public function testSpawnsAttackers(): void
    {
        Artisan::call('game:ai:spawn', ['--race' => 'orc', '--count' => 1, '--type' => 'attacker', '--round' => $this->round->id]);

        $bot = $this->graveyard->dominions()->firstOrFail();
        $this->assertEquals('Orc', $bot->race->name);
        $this->assertEquals(AIHelper::STRATEGY_ATTACKER, $bot->ai_config['strategy']);
        $this->assertEquals('unit1', $bot->ai_config['offense']);
        $this->assertGreaterThanOrEqual(300, $bot->military_unit1);
        $this->assertLessThanOrEqual(350, $bot->military_unit1);
        $this->assertEquals(AIHelper::ATTACKER_STARTING_DOCKS, $bot->building_dock);
        $this->assertEquals(AIHelper::ATTACKER_STARTING_DOCKS, $bot->land_water);
        $this->assertGreaterThanOrEqual(AIHelper::ATTACKER_STARTING_BOATS, $bot->resource_boats);

        $incomingUnit1 = $this->app->make(QueueService::class)->getTrainingQueueTotalByResource($bot, 'military_unit1');
        $this->assertGreaterThanOrEqual(300, $incomingUnit1);
        $this->assertLessThanOrEqual(350, $incomingUnit1);
    }

    public function testAttackerStartingDocksKeepTotalLand(): void
    {
        Artisan::call('game:ai:spawn', ['--race' => 'Orc', '--type' => 'attacker', '--round' => $this->round->id, '--land' => 500]);

        $bot = $this->graveyard->dominions()->firstOrFail();
        $this->assertEquals(500, $this->app->make(LandCalculator::class)->getTotalLand($bot));
        $this->assertEquals(500, $this->app->make(BuildingCalculator::class)->getTotalBuildings($bot));
        $this->assertEquals(90, $bot->building_smithy);
    }

    public function testBoatlessAttackersGetNoDocksOrBoats(): void
    {
        global $mockRandomChance;
        $mockRandomChance = false;

        Artisan::call('game:ai:spawn', ['--race' => 'Spirit', '--type' => 'attacker', '--round' => $this->round->id]);
        $mockRandomChance = null;

        $bot = $this->graveyard->dominions()->firstOrFail();
        $this->assertGreaterThanOrEqual(300, $bot->military_unit1);
        $this->assertEquals(0, $bot->building_dock);
        $this->assertEquals(0, $bot->resource_boats);
        $this->assertGreaterThanOrEqual(rceil(AIHelper::ATTACKER_SMITHY_PERCENTAGE * $this->app->make(LandCalculator::class)->getTotalLand($bot)), $bot->building_smithy);
    }

    public function testRejectsUnsupportedAttackerRace(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not supported as an attacker');

        try {
            Artisan::call('game:ai:spawn', ['--race' => 'Human', '--type' => 'attacker', '--round' => $this->round->id]);
        } finally {
            $this->assertEquals(0, $this->graveyard->dominions()->count());
        }
    }

    public function testRejectsInvalidType(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid type');

        Artisan::call('game:ai:spawn', ['--race' => 'Orc', '--type' => 'defender', '--round' => $this->round->id]);
    }

    public function testRejectsUnknownRace(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No playable race found');

        Artisan::call('game:ai:spawn', ['--race' => 'Dragon', '--round' => $this->round->id]);
    }

    public function testRequiresRoundAndRace(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Both --round and --race are required');

        Artisan::call('game:ai:spawn', ['--race' => 'Orc']);
    }

    public function testRejectsUnknownRound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No round found');

        Artisan::call('game:ai:spawn', ['--round' => 0, '--race' => 'Orc']);
    }
}
