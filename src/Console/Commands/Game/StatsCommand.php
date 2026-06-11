<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\TickService;

class StatsCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:stats';

    /** @var string The console command description. */
    protected $description = 'Generates game stats';

    public function handle(): void
    {
        $tickService = app(TickService::class);

        foreach (Round::activeRankings()->get() as $round) {
            $result = $tickService->sendDailyStats($round);
            if (!config('app.discord_stats_webhook')) {
                $this->info($result['output']);
                $this->info($result['topLand']);
            }
        }
    }
}
