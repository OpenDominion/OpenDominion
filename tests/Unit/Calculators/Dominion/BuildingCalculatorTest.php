<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use CoreDataSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Models\Race;
use OpenDominion\Tests\BaseTestCase;

class BuildingCalculatorTest extends BaseTestCase
{
    use DatabaseMigrations;

    public function testGetTotalBuildings()
    {
        $this->seed(CoreDataSeeder::class);
        $user = $this->createUser();
        $round = $this->createRound();
        $race = Race::where('alignment', 'good')->firstOrFail();
        $dominion = $this->createDominion($user, $round, $race);

        $buildingCalculator = $this->app->make(BuildingCalculator::class)
            ->setDominion($dominion);

        $this->assertEquals(90, $buildingCalculator->getTotalBuildings());
    }
}
