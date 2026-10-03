<?php

namespace OpenDominion\HeroCombat\Persistence;

use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectScope;

/**
 * Serializes a complete BattleState, used to store a battle's starting point for replays.
 */
class StateSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public function capture(BattleState $state): array
    {
        $teamEffects = [];
        foreach ($state->teamEffects as $team => $instances) {
            $teamEffects[$team] = array_map(fn (EffectInstance $i) => $i->toArray(), $instances);
        }

        return [
            'turn' => $state->turn,
            'seed' => $state->seed,
            'mode' => $state->mode,
            'encounter' => $state->encounterKey,
            'sequence' => $state->effectSequence,
            'team_effects' => $teamEffects,
            'field_effects' => array_map(fn (EffectInstance $i) => $i->toArray(), $state->fieldEffects),
            'combatants' => array_values(array_map(fn (CombatantState $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'team' => $c->team,
                'stats' => $c->baseStats,
                'health' => $c->currentHealth,
                'abilities' => $c->abilities,
                'ai' => $c->ai,
                'hero' => $c->heroId,
                'dominion' => $c->dominionId,
                'template' => $c->templateKey,
                'effects' => array_map(fn (EffectInstance $i) => $i->toArray(), $c->effects),
                'cooldowns' => $c->cooldowns,
                'charges' => $c->charges,
                'last_action' => $c->lastAction,
            ], $state->combatants)),
        ];
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public function restore(array $snapshot, ?int $battleId = null): BattleState
    {
        $state = new BattleState($battleId, $snapshot['turn'], $snapshot['seed'], $snapshot['mode'], $snapshot['encounter']);
        $state->effectSequence = $snapshot['sequence'];

        foreach ($snapshot['team_effects'] as $team => $rows) {
            $state->teamEffects[(int) $team] = array_map(function (array $row) use ($team) {
                $instance = EffectInstance::fromArray($row);
                $instance->scope = EffectScope::Team;
                $instance->ownerTeam = (int) $team;
                return $instance;
            }, $rows);
        }

        $state->fieldEffects = array_map(function (array $row) {
            $instance = EffectInstance::fromArray($row);
            $instance->scope = EffectScope::Field;
            return $instance;
        }, $snapshot['field_effects']);

        foreach ($snapshot['combatants'] as $row) {
            $combatant = new CombatantState(
                id: $row['id'],
                name: $row['name'],
                team: $row['team'],
                baseStats: $row['stats'],
                currentHealth: $row['health'],
                abilities: $row['abilities'],
                ai: $row['ai'],
                heroId: $row['hero'],
                dominionId: $row['dominion'],
                templateKey: $row['template'],
            );
            $combatant->effects = array_map(function (array $effectRow) use ($combatant) {
                $instance = EffectInstance::fromArray($effectRow);
                $instance->ownerId = $combatant->id;
                return $instance;
            }, $row['effects']);
            $combatant->cooldowns = $row['cooldowns'];
            $combatant->charges = $row['charges'];
            $combatant->lastAction = $row['last_action'];
            $combatant->automated = !$combatant->isHuman();
            $combatant->deathProcessed = !$combatant->isAlive();
            $state->addCombatant($combatant);
        }

        return $state;
    }
}
