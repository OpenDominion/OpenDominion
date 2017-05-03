<?php

namespace OpenDominion\Console\Commands\Game\Round;

use Illuminate\Console\Command;
use Log;

class EndCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'game:round-end';

    /** @var string The console command description */
    protected $description = 'Ends the current round';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Ending current round');
    }
}
