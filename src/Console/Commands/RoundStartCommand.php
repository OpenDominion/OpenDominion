<?php

namespace OpenDominion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundStartCommand extends Command
{
    protected $signature = 'round:start {--open}';

    protected $description = 'Starts a new round (dev only)';

    /**
     * @inheritdoc
     */
    public function handle()
    {
        $this->output->writeln('<info>Attempting to start a new round</info>');

        $standardRoundLeague = RoundLeague::where('key', 'standard')
            ->firstOrFail();

        $lastRound = Round::where('round_league_id', $standardRoundLeague->id)
            ->orderBy('number', 'desc')
            ->firstOrFail();

        $startDate = $this->option('open') ? new Carbon('midnight') : new Carbon('+5 days midnight');

        $newRound = Round::create([
            'round_league_id' => $standardRoundLeague->id,
            'number' => ($lastRound->number + 1),
            'name' => 'Development Round',
            'start_date' => $startDate,
            'end_date' => new Carbon('+55 days midnight'),
        ]);

        $this->output->writeln("<info>Round {$newRound->number} created successfully</info>");
    }
}
