<?php

namespace OpenDominion\Console\Commands\Game;

use Illuminate\Console\Command;
use OpenDominion\Console\Commands\CommandInterface;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Helpers\AIHelper;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\Dominion\TickService;
use RuntimeException;

class AISpawnCommand extends Command implements CommandInterface
{
    /** @var string The name and signature of the console command. */
    protected $signature = 'game:ai:spawn
                             {--round= : Round ID}
                             {--race= : Race name or key}
                             {--count=1 : Number of non-player dominions to spawn}
                             {--type=explorer : Bot strategy (explorer or attacker)}
                             {--land= : Starting land size (defaults to the standard bot distribution)}';

    /** @var string The console command description. */
    protected $description = 'Spawns non-player dominions of a race in The Graveyard';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $aiHelper = app(AIHelper::class);
        $dominionFactory = app(DominionFactory::class);
        $queueService = app(QueueService::class);
        $tickService = app(TickService::class);

        $roundId = $this->option('round');
        $raceName = $this->option('race');
        $type = $this->option('type');
        $count = (int) $this->option('count');
        $land = $this->option('land');

        if ($roundId === null || $raceName === null) {
            throw new RuntimeException('Both --round and --race are required');
        }

        if (!in_array($type, [AIHelper::STRATEGY_EXPLORER, AIHelper::STRATEGY_ATTACKER], true)) {
            throw new RuntimeException(sprintf('Invalid type "%s", expected explorer or attacker', $type));
        }

        if ($count < 1) {
            throw new RuntimeException('Count must be at least 1');
        }

        if ($land !== null && (int) $land < 1) {
            throw new RuntimeException('Land must be at least 1');
        }

        $race = Race::query()
            ->where('playable', true)
            ->where(function ($query) use ($raceName) {
                $query->where('name', $raceName)->orWhere('key', $raceName);
            })
            ->first();

        if ($race === null) {
            throw new RuntimeException(sprintf('No playable race found for "%s"', $raceName));
        }

        if ($type === AIHelper::STRATEGY_ATTACKER && !$aiHelper->isAttackerRace($race)) {
            throw new RuntimeException(sprintf(
                '%s is not supported as an attacker, supported races: %s',
                $race->name,
                implode(', ', $aiHelper->getAttackerRaces())
            ));
        }

        $round = Round::find($roundId);

        if ($round === null) {
            throw new RuntimeException(sprintf('No round found with ID %s', $roundId));
        }

        $graveyard = $round->graveyard();
        if ($graveyard === null) {
            throw new RuntimeException(sprintf('Round %s has no graveyard realm', $round->name));
        }

        $spawned = 0;
        for ($i = 0; $i < $count; $i++) {
            $landSize = $land !== null ? (int) $land : $dominionFactory->getRandomNonPlayerLandSize();

            try {
                $dominion = $dominionFactory->createRandomNonPlayer($graveyard, $race, $landSize);
            } catch (GameException $e) {
                throw new RuntimeException($e->getMessage(), 0, $e);
            }

            if ($dominion === null) {
                $this->warn('Unable to find an unused name, skipping one dominion');
                continue;
            }

            if ($type === AIHelper::STRATEGY_ATTACKER) {
                $dominion->update($aiHelper->getAttackerStartingAttributes($dominion));
                foreach ($aiHelper->getAttackerIncomingOffense() as $hours => $amount) {
                    $queueService->queueResources('training', $dominion, ['military_unit1' => $amount], $hours);
                }
            }

            // Tick ahead
            $tickService->precalculateTick($dominion);
            $tickService->performTick($round, $dominion);

            $dominion->refresh();
            $dominion->ai_enabled = true;
            $dominion->ai_config = $type === AIHelper::STRATEGY_ATTACKER
                ? $aiHelper->generateAttackerConfig($race)
                : $aiHelper->generateConfig($race);
            $dominion->save();

            $spawned++;
            $this->info(sprintf('Spawned %s %s #%d: %s (%d acres)', $race->name, $type, $dominion->id, $dominion->name, $landSize));
        }

        $this->info(sprintf('Spawned %d of %d %s %s dominion(s) in %s', $spawned, $count, $race->name, $type, $round->name));
    }
}
