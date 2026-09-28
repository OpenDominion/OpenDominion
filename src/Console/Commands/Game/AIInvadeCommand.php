<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\AIService;

class AIInvadeCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:ai:invade';

    /** @var string The console command description. */
    protected $description = 'Attempts invasions for attacking AI dominions scheduled for the current minute';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        app(AIService::class)->executeInvasions();
    }
}
