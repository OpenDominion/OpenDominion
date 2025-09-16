<?php

namespace OpenDominion\Services\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleAction;
use OpenDominion\Models\HeroBattleQueue;
use OpenDominion\Models\HeroCombatant;
use OpenDominion\Models\RaidContribution;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Services\NotificationService;

class HeroBattleService
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var HeroHelper */
    protected $heroHelper;

    /** @var ProtectionService */
    protected $protectionService;

    /**
     * HeroBattleService constructor.
     */
    public function __construct()
    {
        $this->heroCalculator = app(HeroCalculator::class);
        $this->heroHelper = app(HeroHelper::class);
        $this->protectionService = app(ProtectionService::class);
    }

    public const DEFAULT_TIME_BANK = 2 * 60 * 60;
    public const DEFAULT_STRATEGY = 'balanced';

    public function createBattle(Dominion $challenger, Dominion $opponent): HeroBattle
    {
        if ($challenger->round_id !== $opponent->round_id) {
            throw new GameException('You cannot challenge a dominion in a different round');
        }

        if ($challenger->id === $opponent->id) {
            throw new GameException('You cannot challenge yourself');
        }

        $challengerHero = $challenger->heroes()->first();
        if ($challengerHero === null) {
            throw new GameException('Challenger must have a hero to battle');
        }

        $opponentHero = $opponent->heroes()->first();
        if ($opponentHero === null) {
            throw new GameException('Opponent must have a hero to battle');
        }

        $heroBattle = HeroBattle::create(['round_id' => $challenger->round_id]);
        $challengerCombatant = $this->createCombatant($heroBattle, $challengerHero);
        $opponentCombatant = $this->createCombatant($heroBattle, $opponentHero);

        // Send Notifications
        $notificationService = app(NotificationService::class);
        $notificationService->queueNotification('hero_battle', ['status' => 'started']);
        $notificationService->sendNotifications($challengerHero->dominion, 'irregular_dominion');
        $notificationService->queueNotification('hero_battle', ['status' => 'started']);
        $notificationService->sendNotifications($opponentHero->dominion, 'irregular_dominion');

        return $heroBattle;
    }

    public function createCombatant(HeroBattle $heroBattle, Hero $hero): HeroCombatant
    {
        $combatStats = $this->heroCalculator->getHeroCombatStats($hero);
        $combatActions = $this->heroHelper->getCombatActions();
        $classAbilities = $combatActions->where('class', $hero->class)->keys();

        return HeroCombatant::create([
            'hero_battle_id' => $heroBattle->id,
            'hero_id' => $hero->id,
            'dominion_id' => $hero->dominion_id,
            'name' => $hero->name,
            'health' => $combatStats['health'],
            'attack' => $combatStats['attack'],
            'defense' => $combatStats['defense'],
            'evasion' => $combatStats['evasion'],
            'focus' => $combatStats['focus'],
            'counter' => $combatStats['counter'],
            'recover' => $combatStats['recover'],
            'current_health' => $combatStats['health'],
            'time_bank' => self::DEFAULT_TIME_BANK,
            'strategy' => self::DEFAULT_STRATEGY,
            'abilities' => $classAbilities->toArray(),
        ]);
    }

    public function createPracticeBattle(Dominion $dominion): HeroBattle
    {
        if ($this->protectionService->isUnderProtection($dominion)) {
            throw new GameException('You cannot battle while under protection');
        }

        if ($dominion->hero == null) {
            throw new GameException('You must have a hero to practice');
        }

        if ($dominion->hero->battles->where('finished', false)->count() > 0) {
            throw new GameException('You already have a battle in progress');
        }

        $heroBattle = HeroBattle::create(['round_id' => $dominion->round_id, 'pvp' => false]);
        $dominionCombatant = $this->createCombatant($heroBattle, $dominion->hero);
        $nonPlayerStats = $this->heroCalculator->getHeroCombatStats($dominion->hero);
        $nonPlayerStats['name'] = 'Evil Twin';
        $nonPlayerStats['abilities'] = ['enrage'];
        $practiceCombatant = $this->createNonPlayerCombatant($heroBattle, $nonPlayerStats);

        return $heroBattle;
    }

    public function createNonPlayerCombatant(HeroBattle $heroBattle, array $combatStats): HeroCombatant
    {
        return HeroCombatant::create([
            'hero_battle_id' => $heroBattle->id,
            'hero_id' => null,
            'dominion_id' => null,
            'name' => $combatStats['name'],
            'health' => $combatStats['health'],
            'attack' => $combatStats['attack'],
            'defense' => $combatStats['defense'],
            'evasion' => $combatStats['evasion'],
            'focus' => $combatStats['focus'],
            'counter' => $combatStats['counter'],
            'recover' => $combatStats['recover'],
            'current_health' => $combatStats['health'],
            'time_bank' => 0,
            'automated' => true,
            'strategy' => $combatStats['strategy'] ?? self::DEFAULT_STRATEGY,
            'abilities' => $combatStats['abilities'] ?? null,
        ]);
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
                'rating' => $dominion->hero->combat_rating
            ]);
            return null;
        } else {
            HeroBattleQueue::where('hero_id', $opponent->hero->id)->delete();
            return $this->createBattle($dominion, $opponent->hero->dominion);
        }
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

    public function processTurn(HeroBattle $heroBattle): bool
    {
        if ($heroBattle->finished) {
            return false;
        }

        $combatants = $heroBattle->combatants->where('current_health', '>', '0');

        // Eject if not all combatants are ready
        foreach ($combatants as $combatant) {
            if (!$combatant->isReady()) {
                return false;
            }
        }

        // Determine which action to take via queue or automated strategy
        foreach ($combatants as $combatant) {
            $nextAction = $this->determineAction($combatant);
            $combatant->current_action = $nextAction['action'];
            $combatant->current_target = $nextAction['target'];
        }

        // Perform the actions and persist results
        foreach ($combatants as $combatant) {
            $actionDefinitions = $this->heroHelper->getAvailableCombatActions($combatant);
            $actionDef = $actionDefinitions->get($combatant->current_action);

            if ($combatant->current_target !== null) {
                // Use specified target
                $target = $combatants->where('id', $combatant->current_target)->first();
            } elseif ($actionDef['type'] == 'hostile') {
                // Attack a random opponent
                $target = $combatants->where('hero_id', '!=', $combatant->hero_id)->random();
            } else {
                // Default to self
                $target = $combatant;
            }

            $result = $this->processAction($combatant, $target, $actionDef);

            $combatant->current_health += $result['health'];
            $target->current_health -= $result['damage'];

            $action = HeroBattleAction::create([
                'hero_battle_id' => $heroBattle->id,
                'combatant_id' => $combatant->id,
                'target_combatant_id' => $target->id,
                'turn' => $heroBattle->current_turn,
                'action' => $combatant->current_action,
                'damage' => $result['damage'],
                'health' => $result['health'],
                'description' => $result['description']
            ]);
        }

        // Prepare combatants for next turn
        foreach ($combatants as $combatant) {
            if ($combatant->current_health > $combatant->health) {
                $combatant->current_health = $combatant->health;
            }
            $combatant->last_action = $combatant->current_action;
            unset($combatant->current_action);
            unset($combatant->current_target);
            $combatant->save();
        }

        $livingCombatants = $combatants->where('current_health', '>', '0');
        if ($livingCombatants->count() == 0) {
            // Everyone was eliminated (draw)
            $this->setWinner($heroBattle, null);
        } elseif ($livingCombatants->where('hero_id', '!=', null)->count() == 0) {
            // All players eliminated, but NPC remains
            $this->setWinner($heroBattle, $livingCombatants->first());
        } elseif ($livingCombatants->count() == 1) {
            // A single player remains
            $this->setWinner($heroBattle, $livingCombatants->first());
        }

        if (!$heroBattle->finished) {
            $heroBattle->increment('current_turn');
            if ($heroBattle->allReady()) {
                $this->processTurn($heroBattle);
            }
        }

        return true;
    }

    public function determineAction(HeroCombatant $combatant): array
    {
        $queuedActions = $combatant->actions ?? [];

        if (count($queuedActions) > 0) {
            $limitedActions = $this->heroHelper->getLimitedCombatActions();
            $nextAction = array_shift($queuedActions);
            $combatant->actions = $queuedActions;
            if (!$limitedActions->contains($nextAction['action']) || $nextAction['action'] != $combatant->last_action) {
                return $nextAction;
            }
        }

        $strategies = $this->heroHelper->getCombatStrategies();
        $strategy = $strategies->get($combatant->strategy) ?? $strategies->get('balanced');
        $options = collect($strategy['options']);

        if ($combatant->has_focus) {
            $options->forget('focus');
        }
        if ($combatant->health < ($combatant->current_health + $combatant->recover)) {
            $options->forget('recover');
        }
        if ($combatant->current_health <= 40 && isset($options['recover'])) {
            $options->forget('focus');
            $options['recover'] = $options['attack'] * 2;
        }
        return ['action' => $this->randomAction($options, $combatant->last_action), 'target' => null];
    }

    public function randomAction(Collection $options, ?string $last_action): string
    {
        $limitedActions = $this->heroHelper->getLimitedCombatActions();

        foreach ($limitedActions as $action) {
            if ($action == $last_action) {
                $options->forget($action);
            }
        }

        return random_choice_weighted($options->toArray());
    }

    public function processAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        if ($actionDef === null) {
            return [
                'damage' => 0,
                'health' => 0,
                'description' => ''
            ];
        }

        $processorMethod = 'process' . ucfirst($actionDef['processor']) . 'Action';

        return $this->$processorMethod($combatant, $target, $actionDef);
    }

    public function processAttackAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $damage = $this->heroCalculator->calculateCombatDamage($combatant, $target);
        $evaded = $this->heroCalculator->calculateCombatEvade($target);
        $combatant->has_focus = false;
        $health = 0;
        $countered = false;

        if ($target->current_action == 'counter') {
            $countered = true;
            $counterDamage = $this->heroCalculator->calculateCombatDamage($target, $combatant, true);
            $health = -$counterDamage;
        }

        $messages = $actionDef['messages'];

        if ($damage > 0 && $evaded) {
            $damageEvaded = $damage;
            $damage = round($damage / 2);
            if ($countered) {
                $description = sprintf(
                    $messages['evaded_countered'],
                    $combatant->name,
                    $damageEvaded,
                    $target->name,
                    $damage,
                    $target->name,
                    $counterDamage
                );
            } else {
                $description = sprintf(
                    $messages['evaded'],
                    $combatant->name,
                    $damageEvaded,
                    $target->name,
                    $damage
                );
            }
        } else {
            if ($countered) {
                $description = sprintf(
                    $messages['countered'],
                    $combatant->name,
                    $damage,
                    $target->name,
                    $counterDamage
                );
            } else {
                $description = sprintf(
                    $messages['hit'],
                    $combatant->name,
                    $damage,
                    $target->name
                );
            }
        }

        return [
            'damage' => $damage,
            'health' => $health,
            'description' => $description
        ];
    }

    public function processDefendAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        return [
            'damage' => 0,
            'health' => 0,
            'description' => sprintf($actionDef['messages']['defend'], $combatant->name)
        ];
    }

    public function processFocusAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $combatant->has_focus = true;

        return [
            'damage' => 0,
            'health' => 0,
            'description' => sprintf($actionDef['messages']['focus'], $combatant->name)
        ];
    }

    public function processCounterAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        return [
            'damage' => 0,
            'health' => 0,
            'description' => sprintf($actionDef['messages']['counter'], $combatant->name)
        ];
    }

    public function processRecoverAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $health = $this->heroCalculator->calculateCombatHeal($combatant);

        return [
            'damage' => 0,
            'health' => $health,
            'description' => sprintf($actionDef['messages']['recover'], $combatant->name, $health)
        ];
    }

    public function processFlurryAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $attackCount = $actionDef['attributes']['attacks'] ?? 1;
        $damagePenalty = $actionDef['attributes']['penalty'] ?? 1;

        $damage = round($this->heroCalculator->calculateCombatDamage($combatant, $target) * $attackCount * $damagePenalty);
        $evaded = $this->heroCalculator->calculateCombatEvade($target);
        $combatant->has_focus = false;
        $health = 0;
        $countered = false;

        if ($target->current_action == 'counter') {
            $countered = true;
            $counterDamage = round($this->heroCalculator->calculateCombatDamage($target, $combatant, true) * $attackCount);
            $health = -$counterDamage;
        }

        $messages = $actionDef['messages'];

        if ($damage > 0 && $evaded) {
            $damageEvaded = $damage;
            $damage = round($damage / 2);
            if ($countered) {
                $description = sprintf(
                    $messages['evaded_countered'],
                    $combatant->name,
                    $attackCount,
                    $damageEvaded,
                    $target->name,
                    $damage,
                    $target->name,
                    $attackCount,
                    $counterDamage
                );
            } else {
                $description = sprintf(
                    $messages['evaded'],
                    $combatant->name,
                    $attackCount,
                    $damageEvaded,
                    $target->name,
                    $damage
                );
            }
        } else {
            if ($countered) {
                $description = sprintf(
                    $messages['countered'],
                    $combatant->name,
                    $attackCount,
                    $damage,
                    $target->name,
                    $attackCount,
                    $counterDamage
                );
            } else {
                $description = sprintf(
                    $messages['hit'],
                    $combatant->name,
                    $attackCount,
                    $damage,
                    $target->name
                );
            }
        }

        return [
            'damage' => $damage,
            'health' => $health,
            'description' => $description
        ];
    }

    public function processVolatileAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $successChance = $actionDef['attributes']['success_chance'] ?? 1;
        $attackBonus = $actionDef['attributes']['attack_bonus'] ?? 1;
        $isSuccess = mt_rand(1, 100) <= ($successChance * 100);

        $combatant->has_focus = false;
        $health = 0;
        $damage = 0;
        $countered = false;

        if ($target->current_action == 'counter') {
            $countered = true;
            $counterDamage = $this->heroCalculator->calculateCombatDamage($target, $combatant, true);
            $health = -$counterDamage;
        }

        $messages = $actionDef['messages'];

        if ($isSuccess) {
            // Success - deal bonus damage to target
            $damage = round($this->heroCalculator->calculateCombatDamage($combatant, $target) * $attackBonus);
            $evaded = $this->heroCalculator->calculateCombatEvade($target);

            if ($damage > 0 && $evaded) {
                $damageEvaded = $damage;
                $damage = round($damage / 2);
                if ($countered) {
                    $description = sprintf(
                        $messages['success_evaded_countered'],
                        $combatant->name,
                        $damageEvaded,
                        $target->name,
                        $damage,
                        $target->name,
                        $counterDamage
                    );
                } else {
                    $description = sprintf(
                        $messages['success_evaded'],
                        $combatant->name,
                        $damageEvaded,
                        $target->name,
                        $damage
                    );
                }
            } else {
                if ($countered) {
                    $description = sprintf(
                        $messages['success_countered'],
                        $combatant->name,
                        $damage,
                        $target->name,
                        $counterDamage
                    );
                } else {
                    $description = sprintf(
                        $messages['success'],
                        $combatant->name,
                        $damage,
                        $target->name
                    );
                }
            }
        } else {
            // Backfire - damage to self instead
            $backfireDamage = $this->heroCalculator->calculateCombatDamage($combatant, $combatant);
            $health = -$backfireDamage;
            $damage = 0;

            if ($countered) {
                $description = sprintf(
                    $messages['backfire_countered'],
                    $combatant->name,
                    $combatant->name,
                    $backfireDamage,
                    $target->name,
                    $counterDamage
                );
            } else {
                $description = sprintf(
                    $messages['backfire'],
                    $combatant->name,
                    $combatant->name,
                    $backfireDamage
                );
            }
        }

        return [
            'damage' => $damage,
            'health' => $health,
            'description' => $description
        ];
    }

    public function processStatAction(HeroCombatant $combatant, HeroCombatant $target, array $actionDef): array
    {
        $stat = $actionDef['attributes']['stat'];
        $value = $actionDef['attributes']['value'];

        if ($actionDef['type'] == 'self') {
            $combatant->increment($stat, $value);
        } else {
            $target->increment($stat, $value);
        }

        return [
            'damage' => 0,
            'health' => 0,
            'description' => sprintf($actionDef['messages']['stat'], $combatant->name)
        ];
    }

    protected function setWinner(HeroBattle $heroBattle, ?HeroCombatant $winner): void
    {
        $heroBattle->winner_combatant_id = $winner ? $winner->id : null;
        $heroBattle->finished = true;
        $heroBattle->save();

        $tournament = $heroBattle->tournaments->first();
        foreach ($heroBattle->combatants as $combatant) {
            $participant = null;
            if ($tournament !== null) {
                $participant = $tournament->participants->where('hero_id', $combatant->hero_id)->first();
            }

            if ($winner == null) {
                if ($combatant->hero !== null && $heroBattle->pvp) {
                    $combatant->hero->increment('stat_combat_draws');
                }
                if ($participant !== null) {
                    $participant->increment('draws');
                }
            } elseif ($combatant->id == $winner->id) {
                if ($combatant->hero !== null && $heroBattle->pvp) {
                    $combatant->hero->increment('stat_combat_wins');
                }
                if ($participant !== null) {
                    $participant->increment('wins');
                }
            } else {
                if ($combatant->hero !== null && $heroBattle->pvp) {
                    $combatant->hero->increment('stat_combat_losses');
                }
                if ($participant !== null) {
                    $participant->increment('losses');
                }
            }
        }

        if ($heroBattle->pvp) {
            $this->updateRatings($heroBattle);
            // Send Notifications
            $notificationService = app(NotificationService::class);
            foreach ($heroBattle->combatants as $combatant) {
                $notificationService->queueNotification('hero_battle', ['status' => 'ended']);
                $notificationService->sendNotifications($combatant->hero->dominion, 'irregular_dominion');
            }
        }

        if ($heroBattle->raid_tactic_id !== null && $winner !== null && $winner->hero !== null) {
            $dominion = $winner->hero->dominion;
            $tactic = $heroBattle->tactic;

            // Modify score by realm activity
            $pointsEarned = $this->raidCalculator->getTacticPointsEarned($dominion, $tactic);
            $score = $this->raidCalculator->getTacticScore($dominion, $tactic);

            // Save dominion changes
            $dominion->stat_raid_score += $pointsEarned;
            $dominion->save(['event' => HistoryService::EVENT_ACTION_RAID_ACTION]);

            // Create contribution record
            RaidContribution::create([
                'realm_id' => $dominion->realm_id,
                'dominion_id' => $dominion->id,
                'raid_objective_id' => $tactic->raid_objective_id,
                'type' => $tactic->type,
                'score' => $score,
            ]);
        }
    }

    protected function updateRatings(HeroBattle $heroBattle): void
    {
        $playerCount = $heroBattle->combatants->count();
        $playerRatings = $heroBattle->combatants->map(function ($combatant) {
            return [
                'id' => $combatant->id,
                'rating' => $combatant->hero->combat_rating
            ];
        })->keyBy('id');

        foreach ($heroBattle->combatants as $combatant) {
            $averageRating = $playerRatings->where('id', '!=', $combatant->id)->average('rating');
            if ($heroBattle->winner_combatant_id == null) {
                $result = 1 / $playerCount;
            } elseif ($combatant->id == $heroBattle->winner_combatant_id) {
                $result = 1;
            } else {
                $result = 0;
            }
            $currentRating = $playerRatings[$combatant->id]['rating'];
            $newRating = $this->heroCalculator->calculateRatingChange($currentRating, $averageRating, $result);
            $combatant->hero->combat_rating = $newRating;
            $combatant->hero->save();
        }
    }
}
