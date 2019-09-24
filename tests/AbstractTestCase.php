<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\TestCase;

abstract class AbstractTestCase extends TestCase
{
    use CreatesApplication;
    use CreatesData;
}
