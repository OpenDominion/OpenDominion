<?php

namespace OpenDominion\Console\Commands\Development;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\HeroCombat\Persistence\BattleReplayer;
use OpenDominion\Models\HeroBattle;

class HeroCombatReplayCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'hero-combat:replay {battle : Hero battle id}';

    /** @var string The console command description. */
    protected $description = 'Re-simulates a hero battle from its seed and queued actions and compares it with the stored log.';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $heroBattle = HeroBattle::findOrFail($this->argument('battle'));
        $result = resolve(BattleReplayer::class)->replay($heroBattle);

        $this->info("Replayed {$result['turns']} turn(s) of battle #{$heroBattle->id}.");

        if ($result['matches']) {
            $this->info('The replay matches the stored combat log.');
            return;
        }

        foreach ($result['mismatches'] as $mismatch) {
            $this->warn("Turn {$mismatch['turn']} differs:");
            $this->line("  stored:   {$mismatch['expected']}");
            $this->line("  replayed: {$mismatch['actual']}");
        }

        $this->error(count($result['mismatches']) . ' turn(s) differ from the stored combat log.');
    }
}
