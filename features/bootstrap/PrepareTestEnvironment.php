<?php

trait PrepareTestEnvironment
{
    /**
     * @static
     * @beforeScenario
     */
    public static function migrateAndSeed()
    {
        Artisan::call('migrate');
        Artisan::call('db:seed');
    }
}
