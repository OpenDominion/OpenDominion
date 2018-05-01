<?php

namespace OpenDominion\Tests\Feature;

use Artisan;
use CoreDataSeeder;
use DB;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Services\Dominion\Actions\SpellActionService;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class TickTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;

    public function testMoraleTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->morale = 64;
        $dominion->save();

        // Test +6 morale below 70
        Artisan::call('game:tick');
        $this->seeInDatabase('dominions', ['id' => $dominion->id, 'morale' => 70]);

        // Test +3 morale above 70
        Artisan::call('game:tick');
        $this->seeInDatabase('dominions', ['id' => $dominion->id, 'morale' => 73]);
    }

    public function testQueuesTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->land_plain = 0;
        $dominion->building_home = 0;
        $dominion->save();

        DB::table('queue_exploration')->insert([
            'dominion_id' => $dominion->id,
            'land_type' => 'plain',
            'amount' => 10,
            'hours' => 3,
        ]);

        // Two queue records in hourly sequence can give errors
        DB::table('queue_exploration')->insert([
            'dominion_id' => $dominion->id,
            'land_type' => 'plain',
            'amount' => 5,
            'hours' => 2,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'home',
            'amount' => 10,
            'hours' => 3,
        ]);

        // Test queue hours 3 -> 2
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 0, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 2])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 2]);

        // Test queue hours 2 -> 1
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 5, 'building_home' => 0])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 1])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 1]);

        // Test queues get processed on hour 0
        Artisan::call('game:tick');
        $this
            ->seeInDatabase('dominions', ['id' => $dominion->id, 'land_plain' => 15, 'building_home' => 10])
            ->dontSeeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain'])
            ->dontSeeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home']);
    }

    public function testQueueShouldntTickLockedDominions()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound('-2 days', '-1 days');
        $dominion = $this->createDominion($user, $round);

        $dominion->fill([
            'peasants' => 0,
            'morale' => 0,
            'spy_strength' => 0,
            'wizard_strength' => 0,
            'resource_platinum' => 0,
            'building_home' => 100,
            'building_alchemy' => 100,
        ])->save();

        $this->assertTrue($dominion->isLocked());

        DB::table('queue_exploration')->insert([
            'dominion_id' => $dominion->id,
            'land_type' => 'plain',
            'amount' => 10,
            'hours' => 3,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'home',
            'amount' => 10,
            'hours' => 3,
        ]);

        Artisan::call('game:tick');

        $this
            ->seeInDatabase('dominions', [
                'id' => $dominion->id,
                'peasants' => 0,
                'morale' => 0,
                'spy_strength' => 0,
                'wizard_strength' => 0,
                'resource_platinum' => 0,
            ])
            ->seeInDatabase('queue_exploration', ['dominion_id' => $dominion->id, 'land_type' => 'plain', 'hours' => 3])
            ->seeInDatabase('queue_construction', ['dominion_id' => $dominion->id, 'building' => 'home', 'hours' => 3]);
    }

    public function testResourcesGetGeneratedOnTheSameHourThatBuildingsComeIn()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $dominion->resource_gems = 0;
        $dominion->resource_mana = 0;
        $dominion->save();

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'diamond_mine',
            'amount' => 20,
            'hours' => 1,
        ]);

        DB::table('queue_construction')->insert([
            'dominion_id' => $dominion->id,
            'building' => 'tower',
            'amount' => 20,
            'hours' => 1,
        ]);

        Artisan::call('game:tick');

        $this->seeInDatabase('dominions', [
            'id' => $dominion->id,
            'resource_gems' => 300,
            'resource_mana' => 500,
        ]);
    }

    // https://github.com/WaveHack/OpenDominion/issues/217
    public function testTheProperAmountOfPlatinumGetsAddedOnTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $round = $this->createRound();
        $dominion1 = $this->createDominion($user1, $round);
        $dominion2 = $this->createDominion($user2, $round);

        $populationCalculator = $this->app->make(PopulationCalculator::class);
        $productionCalculator = $this->app->make(ProductionCalculator::class);
        $spellActionService = $this->app->make(SpellActionService::class);
        $spellCalculator = $this->app->make(SpellCalculator::class);

        $dominion1->fill([
            'peasants' => 30000,
            'resource_platinum' => 1000,
            'resource_mana' => 9999999,
            'building_alchemy' => 850,
        ])->save();

        // just duplicate values, yolo
        $dominion2->fill([
            'peasants' => 30000,
            'resource_platinum' => 1000,
            'resource_mana' => 9999999,
            'building_alchemy' => 850,
        ])->save();

        // 850 alch * 45 plat = 38,250 plat
        // 18k jobs * 2.7 plat = 48,600 plat

        $platToBeAdded = 86850;

        $this->assertEquals(18000, $populationCalculator->getPopulationEmployed($dominion1));
        $this->assertEquals($platToBeAdded, $productionCalculator->getPlatinumProduction($dominion1));
        $this->assertFalse($spellCalculator->isSpellActive($dominion1, 'midas_touch'));

        $this->assertEquals(18000, $populationCalculator->getPopulationEmployed($dominion2));
        $this->assertEquals($platToBeAdded, $productionCalculator->getPlatinumProduction($dominion2));
        $this->assertFalse($spellCalculator->isSpellActive($dominion2, 'midas_touch'));

        // cast self spell for dominion 2 ONLY

        /** @noinspection PhpUnhandledExceptionInspection */
        $spellActionService->castSelfSpell($dominion2, 'midas_touch');

        // Refresh active spells
        $spellCalculator->getActiveSpells($dominion1, true);
        $this->assertFalse($spellCalculator->isSpellActive($dominion1, 'midas_touch'));
        $this->assertEquals($platToBeAdded * 1.0, $productionCalculator->getPlatinumProduction($dominion1));

        $spellCalculator->getActiveSpells($dominion2, true);
        $this->assertTrue($spellCalculator->isSpellActive($dominion2, 'midas_touch'));
        $this->assertEquals($platToBeAdded * 1.1, $productionCalculator->getPlatinumProduction($dominion2));

        Artisan::call('game:tick');
        $dominion1->refresh();
        $dominion2->refresh();

        $this->assertEquals(1000 + $platToBeAdded * 1.0, $dominion1->resource_platinum);
        $this->assertEquals(1000 + $platToBeAdded * 1.1, $dominion2->resource_platinum);
    }

    // https://github.com/WaveHack/OpenDominion/issues/227
    public function testTheProperAmountOfFoodGetsAddedOnTick()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $dominion = $this->createDominion($user, $round);

        $productionCalculator = $this->app->make(ProductionCalculator::class);
        $spellActionService = $this->app->make(SpellActionService::class);
        $spellCalculator = $this->app->make(SpellCalculator::class);

        $dominion->fill([
            'peasants' => 7553 * 4, // each person eats 0.25 food /hr
            'resource_food' => 27487,
            'resource_mana' => 9999999,
            'building_farm' => 80,
            'military_draftees' => 0,
            'military_unit2' => 0,
            'military_spies' => 0,
            'military_wizards' => 0,
        ])->save();

        // 80 farms * 80 food * 1.05 human * 1.025 prestige = 6888 food
        $this->assertEquals(6888, $productionCalculator->getFoodProduction($dominion));
        $this->assertEquals(7553, $productionCalculator->getFoodConsumption($dominion));
        $this->assertEquals(275, round($productionCalculator->getFoodDecay($dominion)));

        // 6888 - 7553 - 275 = -940 food
        $this->assertEquals(-940, $productionCalculator->getFoodNetChange($dominion));

        /** @noinspection PhpUnhandledExceptionInspection */
        $spellActionService->castSelfSpell($dominion, 'gaias_watch');

        // Refresh active spells
        $spellCalculator->getActiveSpells($dominion, true);

        // 80 farms * 80 food * 1.15 human+gaias * 1.025 prestige = 7544 food
        $this->assertEquals(7544, $productionCalculator->getFoodProduction($dominion));
        $this->assertEquals(7553, $productionCalculator->getFoodConsumption($dominion));
        $this->assertEquals(275, round($productionCalculator->getFoodDecay($dominion)));

        // 7544 - 7553 - 275 = -284
        $this->assertEquals(-284, $productionCalculator->getFoodNetChange($dominion));

        Artisan::call('game:tick');
        $dominion->refresh();

        // 27487 food - 284 net change = 27203 food
        $this->assertEquals(27203, $dominion->resource_food);
    }
}
