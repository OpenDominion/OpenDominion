<?php

namespace OpenDominion\Console\Commands\Game;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Factories\RoundFactory;
use OpenDominion\Models\RoundLeague;
use RuntimeException;

class RoundOpenCommand extends Command
{
    protected $signature = 'game:round:open
                             {--now : Start the round right now (dev & testing only)}
                             {--open : Start the round in +3 days midnight, allowing for immediate registration}
                             {--league=standard : Round league to use}';

    protected $description = 'Creates a new round which starts in 5 days';

    /** @var RoundFactory  */
    protected $roundFactory;

    public function __construct(RoundFactory $roundFactory)
    {
        parent::__construct();

        $this->roundFactory = $roundFactory;
    }

    public function handle()
    {
        $now = $this->option('now');
        $open = $this->option('open');
        $league = $this->option('league');

        if ($now && (app()->environment() === 'production')) {
            throw new RuntimeException('Option --now may not be used on production');
        }

        if ($now && $open) {
            throw new RuntimeException('Options --now and --open are mutually exclusive');
        }

        if ($now) {
            $startDate = 'now';
        } elseif ($open) {
            $startDate = '+3 days midnight';
        } else {
            $startDate = '+5 days midnight';
        }

        $startDate = new Carbon($startDate);
        $roundLeague = RoundLeague::where('key', $league)->firstOrFail();

        $this->info("Starting a new round in {$roundLeague->key} league");

        $round = $this->roundFactory->create($roundLeague, $startDate);

        $this->info("Round {$round->number} created in {$roundLeague->key} league, starting at {$round->start_date}");
    }
}
