<?php

namespace OpenDominion\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use OpenDominion\Services\Dominion\TickService;

class DailyRankingsAndStatsJob implements ShouldQueue
{
    use Queueable;

    public function handle(TickService $tickService): void
    {
        $tickService->updateDailyRankings();
        $tickService->tickDailyStats();
    }
}
