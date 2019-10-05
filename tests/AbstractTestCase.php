<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\TestCase;
use OpenDominion\Tests\Traits\CreatesApplication;
use OpenDominion\Tests\Traits\CreatesData;

abstract class AbstractTestCase extends TestCase
{
    use CreatesApplication;
    use CreatesData;
}
