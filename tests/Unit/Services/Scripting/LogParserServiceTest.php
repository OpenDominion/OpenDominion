<?php

namespace OpenDominion\Tests\Unit\Services\Scripting;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use OpenDominion\Services\Scripting;
use OpenDominion\Tests\AbstractBrowserKitTestCase;

class LogParserServiceTest extends AbstractBrowserKitTestCase
{
    
    protected function setUp()
    {
        parent::setUp();
    }

    public function testSomething()
    {
        $service = new \OpenDominion\Services\Scripting\LogParserService();

        $actions = $service->parselogfile('');

        print_r($actions);
    }
}