<?php

namespace OpenDominion\HeroCombat\Persistence;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectScope;
use OpenDominion\HeroCombat\Engine\Events\LogEntry;
use OpenDominion\HeroCombat\Engine\Random\SeededRandomSource;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroBattleTurn;
use OpenDominion\Models\HeroCombatant;

/**
 * Maps hero battle models to engine state and back.
 */
class BattleRepository
{
    public function __construct(
        protected CombatRegistry $registry,
        protected StateSnapshot $snapshot = new StateSnapshot(),
    ) {
    }

    /**
     * Reloads the battle row with a write lock. Call inside a database transaction.
     */
    public function lock(HeroBattle $heroBattle): HeroBattle
    {
        return HeroBattle::query()->with('combatants')->lockForUpdate()->findOrFail($heroBattle->id);
    }

    public function load(HeroBattle $heroBattle): Battle
    {
        $state = new BattleState(
            id: $heroBattle->id,
            turn: $heroBattle->current_turn,
            seed: (int) $heroBattle->seed,
            mode: $heroBattle->mode ?? BattleState::MODE_PVP,
            encounterKey: $heroBattle->encounter_key,
        );
        $state->finished = (bool) $heroBattle->finished;
        $state->winningTeam = $heroBattle->winning_team;

        $effects = $heroBattle->effects ?? [];
        foreach ($effects['team'] ?? [] as $team => $rows) {
            $state->teamEffects[(int) $team] = array_map(
                fn (array $row) => $this->instance($row, EffectScope::Team, null, (int) $team),
                $this->knownEffects($rows),
            );
        }
        $state->fieldEffects = array_map(
            fn (array $row) => $this->instance($row, EffectScope::Field, null, null),
            $this->knownEffects($effects['field'] ?? []),
        );

        foreach ($heroBattle->combatants as $model) {
            $state->addCombatant($this->combatant($model));
        }

        $sequences = array_map(fn (EffectInstance $instance) => $instance->sequence, $this->allInstances($state));
        $state->effectSequence = $sequences === [] ? 0 : max($sequences);

        return new Battle(
            $state,
            $this->registry,
            SeededRandomSource::forTurn($state->seed, $state->turn),
            new EloquentCombatantSpawner(),
        );
    }

    public function persist(Battle $battle, HeroBattle $heroBattle): void
    {
        $state = $battle->state;

        $teamEffects = [];
        foreach ($state->teamEffects as $team => $instances) {
            if ($instances !== []) {
                $teamEffects[$team] = array_map(fn (EffectInstance $i) => $i->toArray(), $instances);
            }
        }
        $fieldEffects = array_map(fn (EffectInstance $i) => $i->toArray(), $state->fieldEffects);

        $creating = $heroBattle->initial_state === null;
        if ($creating) {
            $heroBattle->initial_state = json_encode($this->snapshot->capture($state));
        }

        $heroBattle->current_turn = $state->turn;
        $heroBattle->finished = $state->finished;
        $heroBattle->winning_team = $state->winningTeam;
        $heroBattle->winner_combatant_id = $this->representative($battle);
        $heroBattle->effects = ($teamEffects === [] && $fieldEffects === []) ? null : ['team' => $teamEffects, 'field' => $fieldEffects];
        $heroBattle->save();

        $models = HeroCombatant::query()->where('hero_battle_id', $heroBattle->id)->get()->keyBy('id');
        foreach ($state->combatants as $combatant) {
            $model = $models->get($combatant->id);
            if ($model === null) {
                continue;
            }
            $this->fillCombatant($model, $combatant);
            $model->save();
        }

        $this->writeTurns($heroBattle, $battle->log->flush(), $creating);

        $heroBattle->unsetRelation('combatants');
        $heroBattle->unsetRelation('turns');
    }

    protected function combatant(HeroCombatant $model): CombatantState
    {
        $combatant = new CombatantState(
            id: $model->id,
            name: $model->name,
            team: (int) $model->team,
            baseStats: [
                Stat::Health->value => (int) $model->health,
                Stat::Attack->value => (int) $model->attack,
                Stat::Defense->value => (int) $model->defense,
                Stat::Evasion->value => (int) $model->evasion,
                Stat::Focus->value => (int) $model->focus,
                Stat::Counter->value => (int) $model->counter,
                Stat::Recover->value => (int) $model->recover,
            ],
            currentHealth: (int) $model->current_health,
            abilities: $model->abilities ?? [],
            ai: $model->strategy ?? 'balanced',
            heroId: $model->hero_id,
            dominionId: $model->dominion_id,
            templateKey: $model->template_key,
        );

        $combatant->effects = array_map(
            fn (array $row) => $this->instance($row, EffectScope::Combatant, $model->id, null),
            $this->knownEffects($model->effects ?? []),
        );
        $combatant->cooldowns = $model->cooldowns ?? [];
        $combatant->charges = $model->charges ?? [];
        $combatant->queue = array_map(fn (array $queued) => [
            'ability' => $queued['ability'] ?? $queued['action'],
            'target' => $queued['target'] ?? null,
        ], $model->actions ?? []);
        $combatant->lastAction = $model->last_action;
        $combatant->automated = (bool) $model->automated;
        $combatant->timeBank = (int) $model->time_bank;
        $combatant->controllerDominionId = $model->controller_dominion_id;
        $combatant->deathProcessed = !$combatant->isAlive();

        return $combatant;
    }

    protected function fillCombatant(HeroCombatant $model, CombatantState $combatant): void
    {
        $model->fill([
            'name' => $combatant->name,
            'team' => $combatant->team,
            'health' => $combatant->baseStat(Stat::Health),
            'attack' => $combatant->baseStat(Stat::Attack),
            'defense' => $combatant->baseStat(Stat::Defense),
            'evasion' => $combatant->baseStat(Stat::Evasion),
            'focus' => $combatant->baseStat(Stat::Focus),
            'counter' => $combatant->baseStat(Stat::Counter),
            'recover' => $combatant->baseStat(Stat::Recover),
            'current_health' => $combatant->currentHealth,
            'abilities' => $combatant->abilities,
            'effects' => array_map(fn (EffectInstance $i) => $i->toArray(), $combatant->effects) ?: null,
            'cooldowns' => $combatant->cooldowns ?: null,
            'charges' => $combatant->charges ?: null,
            'actions' => $combatant->queue,
            'last_action' => $combatant->lastAction,
            'automated' => $combatant->automated,
            'strategy' => $combatant->ai,
        ]);
    }

    /**
     * Stores each turn's events in the order they happened, one row per turn. Events from
     * setting the battle up are stored as turn 0, since the initial snapshot already includes them.
     *
     * @param LogEntry[] $entries
     */
    protected function writeTurns(HeroBattle $heroBattle, array $entries, bool $setup = false): void
    {
        $turns = [];
        foreach ($entries as $entry) {
            foreach ($entry->events as $event) {
                $turns[$setup ? 0 : $entry->turn][] = $event->toArray();
            }
        }

        foreach ($turns as $turn => $events) {
            HeroBattleTurn::record($heroBattle, $turn, $events);
        }
    }

    /**
     * First human on the winning team, else its first combatant. Kept for older views and
     * reports that read winner_combatant_id.
     */
    protected function representative(Battle $battle): ?int
    {
        if (!$battle->state->finished || $battle->state->winningTeam === null) {
            return null;
        }

        $winners = array_filter($battle->combatants(), fn ($c) => $c->team === $battle->state->winningTeam);
        $humans = array_filter($winners, fn ($c) => $c->isHero());

        $representative = reset($humans) ?: reset($winners);

        return $representative ? $representative->id : null;
    }

    /**
     * Saved effect rows whose effect still exists. Removed content (such as Last Stand, Mana
     * Shield and Sunder) is dropped, so battles saved before its removal still load.
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, array<string, mixed>>
     */
    protected function knownEffects(array $rows): array
    {
        return array_values(array_filter($rows, fn (array $row) => $this->registry->hasEffect($row['key'])));
    }

    /**
     * @param array<string, mixed> $row
     */
    protected function instance(array $row, EffectScope $scope, ?int $ownerId, ?int $ownerTeam): EffectInstance
    {
        $instance = EffectInstance::fromArray($row);
        $instance->scope = $scope;
        $instance->ownerId = $ownerId;
        $instance->ownerTeam = $ownerTeam;

        return $instance;
    }

    /**
     * @return EffectInstance[]
     */
    protected function allInstances(BattleState $state): array
    {
        $instances = $state->fieldEffects;
        foreach ($state->teamEffects as $teamInstances) {
            array_push($instances, ...$teamInstances);
        }
        foreach ($state->combatants as $combatant) {
            array_push($instances, ...$combatant->effects);
        }

        return $instances;
    }
}
