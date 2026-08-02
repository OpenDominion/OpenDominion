<?php

namespace OpenDominion\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase;
use OpenDominion\Tests\Traits\CreatesApplication;
use OpenDominion\Tests\Traits\CreatesData;

abstract class AbstractTestCase extends TestCase
{
    use CreatesApplication;
    use CreatesData;
    /**
     * Roll back everything each test writes.
     *
     * Without this the suite leaves its rows behind, and UserFactory's
     * faker->unique() only dedupes within a single run — so duplicate email
     * collisions become steadily more likely as the database fills up.
     * DatabaseTransactions rather than RefreshDatabase, because the suite reads
     * seeded reference data (races, units, spells) that a fresh migration drops.
     */
    use DatabaseTransactions;
}
