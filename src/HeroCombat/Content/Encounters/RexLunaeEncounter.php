<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Grants Cleanse: without it the Hungry Moon's curse cannot be answered.
 */
class RexLunaeEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'rex_lunae';
    }

    public function name(): string
    {
        return 'Rex Lunae';
    }

    public function source(): string
    {
        return 'Raid (Plague of the Hungry Moon)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'rex_lunae', 'name' => 'Rex Lunae'],
        ];
    }

    public function playerGrants(CombatantState $player, array $heroClasses): array
    {
        return ['cleanse'];
    }
}
