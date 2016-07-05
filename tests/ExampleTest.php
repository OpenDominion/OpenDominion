<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ExampleTest extends BaseTestCase
{
    public function testBasicExample()
    {
        $this->visit('/')
             ->see('Home content');
    }
}
