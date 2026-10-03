<?php

namespace OpenDominion\HeroCombat\Persistence;

use OpenDominion\HeroCombat\Contracts\CombatantSpawner;
use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\Models\HeroCombatant;

/**
 * Inserts the combatant row immediately so it has a real id; the rest of its state is
 * written when the battle is persisted.
 */
class EloquentCombatantSpawner implements CombatantSpawner
{
    public function spawn(BattleState $state, CombatantState $combatant): int
    {
        $model = HeroCombatant::create([
            'hero_battle_id' => $state->id,
            'hero_id' => $combatant->heroId,
            'dominion_id' => $combatant->dominionId,
            'team' => $combatant->team,
            'template_key' => $combatant->templateKey,
            'name' => $combatant->name,
            'health' => $combatant->baseStat(Stat::Health),
            'attack' => $combatant->baseStat(Stat::Attack),
            'defense' => $combatant->baseStat(Stat::Defense),
            'evasion' => $combatant->baseStat(Stat::Evasion),
            'focus' => $combatant->baseStat(Stat::Focus),
            'counter' => $combatant->baseStat(Stat::Counter),
            'recover' => $combatant->baseStat(Stat::Recover),
            'current_health' => $combatant->currentHealth,
            'time_bank' => $combatant->timeBank,
            'automated' => $combatant->automated,
            'strategy' => $combatant->ai,
            'abilities' => $combatant->abilities,
        ]);

        return $model->id;
    }
}
