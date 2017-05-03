<?php

namespace OpenDominion\Console\Commands\Game\Round;

use Illuminate\Console\Command;
use Log;

class OpenCommand extends Command
{
    /** @var string The name and signature of the console command */
    protected $signature = 'game:round-open';

    /** @var string The console command description */
    protected $description = 'Opens a new round for registering';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        Log::info('Opening new round');
    }
}
