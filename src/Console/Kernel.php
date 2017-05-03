<?php

namespace OpenDominion\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Game\Round\EndCommand::class,
        Commands\Game\Round\OpenCommand::class,
        Commands\Game\Round\StartCommand::class,
        Commands\Game\TickCommand::class,
        Commands\UpdateVersionCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('game:tick')
            ->hourly();

        $schedule->command('game:round-start')
            ->monthlyOn(1);

        $schedule->command('game:round-end')
            ->monthlyOn(25);

        $schedule->command('game:round-open')
            ->monthlyOn(27);
    }

    /**
     * Register the Closure based commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        require base_path('app/routes/console.php');
    }
}
