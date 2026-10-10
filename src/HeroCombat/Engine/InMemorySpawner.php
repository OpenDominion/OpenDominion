<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Contracts\CombatantSpawner;

/**
 * Assigns the next free id without persisting anything (tests and replays).
 */
final class InMemorySpawner implements CombatantSpawner
{
    public function spawn(BattleState $state, CombatantState $combatant): int
    {
        return $state->combatants === [] ? 1 : max(array_keys($state->combatants)) + 1;
    }
}
