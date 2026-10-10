<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Facades\DB;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleEngine;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\EncounterContext;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\HeroCombat\Persistence\BattleRepository;
use OpenDominion\HeroCombat\Persistence\CombatantFactory;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleQueue;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Round;
use OpenDominion\Services\NotificationService;

/**
 * Application-facing entry point for hero battles: creating them, matchmaking, and
 * advancing turns. The rules themselves live in src/HeroCombat.
 */
class HeroBattleService
{
    public const PLAYER_TEAM = 1;
    public const ENEMY_TEAM = 2;

    public function __construct(
        protected CombatRegistry $registry,
        protected BattleRepository $repository,
        protected CombatantFactory $combatantFactory,
        protected HeroClassLoadouts $loadouts,
        protected HeroCalculator $heroCalculator,
        protected HeroBattleOutcomeService $outcomeService,
        protected ProtectionService $protectionService,
        protected BattleEngine $engine = new BattleEngine(),
    ) {
    }

    /**
     * A 1v1 PvP battle.
     */
    public function createBattle(Dominion $challenger, Dominion $opponent): HeroBattle
    {
        if ($challenger->round_id !== $opponent->round_id) {
            throw new GameException('You cannot challenge a dominion in a different round');
        }

        if ($challenger->id === $opponent->id) {
            throw new GameException('You cannot challenge yourself');
        }

        if ($challenger->hero === null) {
            throw new GameException('Challenger must have a hero to battle');
        }

        if ($opponent->hero === null) {
            throw new GameException('Opponent must have a hero to battle');
        }

        return $this->createTeamBattle([[$challenger], [$opponent]]);
    }

    /**
     * A PvP battle between teams of dominions; team numbers follow array order.
     *
     * @param array<int, Dominion[]> $teams
     */
    public function createTeamBattle(array $teams): HeroBattle
    {
        $dominions = array_merge(...$teams);

        $heroBattle = DB::transaction(function () use ($teams, $dominions) {
            $heroBattle = $this->newBattle($dominions[0]->round_id, BattleState::MODE_PVP);
            $battle = $this->repository->load($heroBattle);

            foreach (array_values($teams) as $index => $members) {
                foreach ($members as $dominion) {
                    $this->combatantFactory->addHero($battle, $dominion->hero, $index + 1);
                }
            }

            $this->repository->persist($battle, $heroBattle);

            return $heroBattle;
        });

        $notificationService = app(NotificationService::class);
        foreach ($dominions as $dominion) {
            $notificationService->queueNotification('hero_battle', ['status' => 'started']);
            $notificationService->sendNotifications($dominion, 'irregular_dominion');
        }

        return $heroBattle;
    }

    public function createPracticeBattle(Dominion $dominion, ?string $encounterKey = null): HeroBattle
    {
        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new GameException('You cannot battle while under protection');
        }

        if ($dominion->hero === null) {
            throw new GameException('You must have a hero to practice');
        }

        if ($dominion->hero->battles->where('finished', false)->count() > 0) {
            throw new GameException('You already have a battle in progress');
        }

        return $this->createEncounterBattle($encounterKey ?? 'default', [$dominion], BattleState::MODE_PRACTICE);
    }

    /**
     * A PvE battle: the dominions' heroes (team 1) against an encounter's roster (team 2).
     *
     * @param Dominion[] $dominions
     */
    public function createEncounterBattle(
        string $encounterKey,
        array $dominions,
        string $mode,
        ?RaidObjectiveTactic $tactic = null,
        int $priorWins = 0,
    ): HeroBattle {
        if (!$this->registry->hasEncounter($encounterKey)) {
            throw new GameException('This encounter is not available.');
        }

        $encounter = $this->registry->encounter($encounterKey);

        return DB::transaction(function () use ($encounter, $dominions, $mode, $tactic, $priorWins) {
            $heroBattle = $this->newBattle($dominions[0]->round_id, $mode, $encounter->key(), $tactic);
            $battle = $this->repository->load($heroBattle);

            foreach ($dominions as $dominion) {
                $combatant = $this->combatantFactory->addHero($battle, $dominion->hero, self::PLAYER_TEAM);
                $grants = $encounter->playerGrants($combatant, $this->combatantFactory->heroClasses($dominion->hero));
                $combatant->abilities = $this->loadouts->withGrants($combatant->abilities, $grants);
            }

            $context = new EncounterContext(
                priorWins: $priorWins,
                leaderStats: $this->heroCalculator->getHeroCombatStats($dominions[0]->hero),
            );

            foreach ($encounter->roster($context) as $entry) {
                $battle->spawn(
                    $this->registry->enemy($entry['template']),
                    self::ENEMY_TEAM,
                    $entry['name'] ?? null,
                    $entry['stats'] ?? [],
                    $entry['effects'] ?? [],
                );
            }

            $this->repository->persist($battle, $heroBattle);

            return $heroBattle;
        });
    }

    public function joinQueue(Dominion $dominion): ?HeroBattle
    {
        $this->clearQueue();

        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new GameException('You cannot battle while under protection');
        }

        if ($dominion->hero == null) {
            throw new GameException('You must have a hero to queue for battles');
        }

        if ($dominion->hero->isInQueue()) {
            throw new GameException('You are already in the queue');
        }

        if ($dominion->hero->battles->where('finished', false)->count() > 0) {
            throw new GameException('You already have a battle in progress');
        }

        $opponent = HeroBattleQueue::query()->first();
        if ($opponent === null) {
            HeroBattleQueue::create([
                'hero_id' => $dominion->hero->id,
                'level' => $this->heroCalculator->getHeroLevel($dominion->hero),
                'rating' => $dominion->hero->combat_rating,
            ]);
            return null;
        }

        HeroBattleQueue::where('hero_id', $opponent->hero->id)->delete();

        return $this->createBattle($dominion, $opponent->hero->dominion);
    }

    public function leaveQueue(Dominion $dominion): void
    {
        if ($dominion->hero == null) {
            throw new GameException('You don\'t have a hero');
        }

        HeroBattleQueue::where('hero_id', $dominion->hero->id)->delete();
    }

    public function clearQueue(): void
    {
        HeroBattleQueue::where('created_at', '<', now()->subHours(1))->delete();
    }

    public function processBattles(Round $round): void
    {
        $battles = HeroBattle::query()
            ->where('round_id', $round->id)
            ->where('finished', false)
            ->get();

        foreach ($battles as $battle) {
            $this->checkTime($battle);
            $this->processTurn($battle);
        }
    }

    /**
     * Spends time banks for humans who have not chosen an action, and switches anyone out
     * of time to automated play.
     */
    public function checkTime(HeroBattle $heroBattle): void
    {
        foreach ($heroBattle->combatants as $combatant) {
            $combatant->time_bank -= $combatant->timeElapsed();
            if ($combatant->time_bank <= 0) {
                $combatant->automated = true;
            }
            $combatant->save();
        }

        $heroBattle->last_processed_at = now();
        $heroBattle->save();
    }

    /**
     * Resolves turns for as long as every living combatant is ready.
     *
     * @return bool whether at least one turn was resolved
     */
    public function processTurn(HeroBattle $heroBattle): bool
    {
        if ($heroBattle->finished) {
            return false;
        }

        return DB::transaction(function () use ($heroBattle) {
            $locked = $this->repository->lock($heroBattle);
            if ($locked->finished) {
                return false;
            }

            $battle = $this->repository->load($locked);
            $turns = $this->engine->advance($battle, function (Battle $battle) {
                $battle->random = SeededRandomSource::forTurn($battle->state->seed, $battle->turn());
            });

            if ($turns === 0) {
                return false;
            }

            $this->repository->persist($battle, $locked);

            if ($battle->state->finished) {
                $this->outcomeService->finalize($locked->fresh());
            }

            $heroBattle->refresh();

            return true;
        });
    }

    /**
     * Engine view of a battle, for validation and display. Not locked; do not persist.
     */
    public function loadBattle(HeroBattle $heroBattle): Battle
    {
        return $this->repository->load($heroBattle);
    }

    protected function newBattle(int $roundId, string $mode, ?string $encounterKey = null, ?RaidObjectiveTactic $tactic = null): HeroBattle
    {
        return HeroBattle::create([
            'round_id' => $roundId,
            'current_turn' => 1,
            'finished' => false,
            'pvp' => $mode === BattleState::MODE_PVP,
            'mode' => $mode,
            'encounter_key' => $encounterKey,
            'raid_tactic_id' => $tactic?->id,
            'seed' => random_int(1, 2_147_483_647),
        ]);
    }
}
