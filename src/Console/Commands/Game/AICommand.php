<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Services\Dominion\AIService;

class AICommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:ai';

    /** @var string The console command description. */
    protected $description = 'Runs the AI dominion actions';

    /** @var AIService */
    protected $aiService;

    /**
     * GameTickCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->aiService = app(AIService::class);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $this->aiService->executeAI();
    }
}
