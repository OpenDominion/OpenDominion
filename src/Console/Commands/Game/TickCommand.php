<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Log;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\TickService;

class TickCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick';

    /** @var string The console command description. */
    protected $description = 'Ticks the game';

    /** @var Carbon */
    protected $now;

    /** @var TickService */
    protected $tickService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->now = now();
        $this->tickService = app(TickService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $activeDominionIds = $this->tickService->tickHourly();

        if (now()->hour === 0) {
            $this->tickService->tickDaily();
        }

        // Update rankings (every 6 hours)
        if($this->now->hour % 6 === 0) {
            Log::debug('Update rankings started');
            $this->tickService->updateDailyRankings($activeDominionIds);
            Log::debug('Update rankings finished');
        }
    }
}
