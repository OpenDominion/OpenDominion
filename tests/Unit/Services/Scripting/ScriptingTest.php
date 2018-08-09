<?php

namespace OpenDominion\Tests\Unit\Services\Scripting;

use Artisan;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\Scripting;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class ScriptingTest extends AbstractBrowserKitTestCase
{
    use DatabaseMigrations;
    
    protected function setUp()
    {
        parent::setUp();

        $this->seedDatabase();
    }

    public function testSomething()
    {
        $service = new \OpenDominion\Services\Scripting\LogParserService();
        
        $draftRateService = app(\OpenDominion\Services\Dominion\Actions\Military\ChangeDraftRateActionService::class);
        $round = $this->createRound();
        $goodRealm = $this->createRealm($round);
        $user = $this->createUser();
        $dominion = $this->createDominion($user, $round);

        $draftRateService->changeDraftRate($dominion, 90);

        $data = file_get_contents('C:\Git\OpenDominion\slz_test_log.txt');

        $actionsPerHours = $service->parselogfile($data);

        // print_r($actionsPerHours);
        $maxHours = max(array_keys($actionsPerHours));
        // print_r($maxHours);
        echo "\n";
        for($hour = 1; $hour <= 72; $hour++)
        {
            $scriptingService = new \OpenDominion\Services\Scripting\ScriptingService();
            $tickService = app(\OpenDominion\Services\Dominion\TickService::class);
            // echo "\n $hour: ";
            // echo "\n$dominion->peasants";
            $popCalc = app(\OpenDominion\Calculators\Dominion\PopulationCalculator::class);
            $maxPop = $popCalc->getMaxPopulationRaw($dominion);
            if(array_key_exists($hour, $actionsPerHours))
            {
                $actionsForHour = $actionsPerHours[$hour];
                $results[$hour][] = $scriptingService->scriptHour($dominion, $actionsForHour);
            }

            $tickService->tickDominion($dominion);
            // echo "\n$dominion->military_draftees";

            // echo "\n$maxPop";
            // echo "\n";
        }

        // print_r($results);
    }
}