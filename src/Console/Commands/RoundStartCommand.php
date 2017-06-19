<?php

namespace OpenDominion\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundLeague;

class RoundStartCommand extends Command
{
    protected $signature = 'round:start';

    protected $description = 'Starts a new round (dev only)';

    public function __construct()
    {
        parent::__construct();

        //
    }

    public function handle()
    {
        $this->output->writeln('<info>Attempting to start a new round</info>');

        $standardRoundLeague = RoundLeague::where('key', 'standard')
            ->firstOrFail();

        $lastRound = Round::where('round_league_id', $standardRoundLeague->id)
            ->orderBy('number', 'desc')
            ->firstOrFail();

        if ($lastRound->isActive()) {
            $this->output->writeln("<error>Did not create a new round because round {$lastRound->number} in {$standardRoundLeague->description} is still active!</error>");
            return false;
        }

        $newRound = Round::create([
            'round_league_id' => $standardRoundLeague->id,
            'number' => ($lastRound->number + 1),
            'name' => 'Development Round',
            'start_date' => new Carbon('+5 days midnight'),
            'end_date' => new Carbon('+55 days midnight'),
        ]);

        $this->output->writeln("<info>Round {$newRound->number} created successfully</info>");
    }
}
