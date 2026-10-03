<?php

namespace OpenDominion\HeroCombat\Contracts;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Intent;

interface AiStrategy
{
    public function key(): string;

    public function name(): string;

    /** Whether players may pick this strategy for their automated turns. */
    public function playerSelectable(): bool;

    public function chooseIntent(CombatantState $actor, Battle $battle): Intent;
}
