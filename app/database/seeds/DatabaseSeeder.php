<?php

use Illuminate\Database\Seeder;
use OpenDominion\Console\Commands\Game\DataSyncCommand;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(CoreDataSeeder::class);

        Artisan::call(DataSyncCommand::class);

        if (app()->environment('local')) {
            $this->call(DevelopmentSeeder::class);
        }
    }
}
