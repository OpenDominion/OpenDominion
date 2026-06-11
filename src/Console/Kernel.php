<?php

namespace OpenDominion\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('game:tick')->hourlyAt(0);
        $schedule->command('game:ai')->hourlyAt(30);

        if (app()->environment('production')) {
            $schedule->command('queue:monitor default --max=10')->hourlyAt(5);
            $schedule->command('backup:clean')->dailyAt('01:20');
            $schedule->command('backup:run')->dailyAt('01:40');
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('app/routes/console.php');
    }
}
