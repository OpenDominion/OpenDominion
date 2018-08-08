<?php

namespace OpenDominion\Tests\Unit\Services\Scripting;

use OpenDominion\Models\Dominion;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\Scripting;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class LogParserServiceTest extends AbstractBrowserKitTestCase
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
        $scriptingService = new \OpenDominion\Services\Scripting\ScriptingService();
        $round = $this->createRound();
        $goodRealm = $this->createRealm($round);
        $user = $this->createUser();
        $dominion = $this->createDominion($user, $round);

        $data = file_get_contents('C:\Git\OpenDominion\slz_test_log.txt');

        $actionsPerHours = $service->parselogfile($data);

        print_r($actionsPerHours);
        foreach($actionsPerHours as $actionsForHour)
        {
            $results[] = $scriptingService->scriptHour($dominion, $actionsForHour);
        }

        print_r($results);
    }
}