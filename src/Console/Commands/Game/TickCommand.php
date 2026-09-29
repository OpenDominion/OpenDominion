<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use Laravel\Pulse\Facades\Pulse;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Jobs\DailyRankingsAndStatsJob;
use OpenDominion\Services\Dominion\TickService;

class TickCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:tick';

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
        // Pulse inspects every query to decide whether to record it, and the
        // tick issues tens of thousands - profiled at ~7% of its PHP time for
        // telemetry about a scheduled command nobody browses in the dashboard.
        // Only this command is affected; web requests keep recording.
        Pulse::stopRecording();

        try {
            DailyRankingsAndStatsJob::dispatch();
            $this->tickService->tickHourly();
            $this->tickService->tickDaily();
        } finally {
            Pulse::startRecording();
        }
    }
}
