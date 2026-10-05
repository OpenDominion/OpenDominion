<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Jobs\DailyRankingsAndStatsJob;
use OpenDominion\Services\Dominion\TickService;

class TickCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick {--recover : Retry due hours for rounds already using the tick ledger}';

    /** @var string The console command description. */
    protected $description = 'Ticks the game';

    /** @var TickService */
    protected $tickService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->tickService = app(TickService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        if ($this->option('recover')) {
            $this->tickService->recoverHourlyTicks();
            return;
        }

        DailyRankingsAndStatsJob::dispatch();
        $this->tickService->tickHourly();
    }
}
