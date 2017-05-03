<?php

namespace OpenDominion\Console\Commands\Game\Round;

use Illuminate\Console\Command;
use Log;

class StartCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'game:round-start';

    /** @var string The console command description */
    protected $description = 'Starts the newly opened round';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Starting new round');
    }
}
