<?php

namespace OpenDominion\HeroCombat\Contracts;

use OpenDominion\HeroCombat\Engine\BattleState;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * Allocates an id for a combatant summoned mid-battle (and persists it if needed).
 */
interface CombatantSpawner
{
    public function spawn(BattleState $state, CombatantState $combatant): int;
}
