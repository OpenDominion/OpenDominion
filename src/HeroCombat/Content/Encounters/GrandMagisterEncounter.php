<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Short self-buffs and hexes that each change which basic action is correct for a turn.
 */
class GrandMagisterEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'grand_magister';
    }

    public function name(): string
    {
        return 'Grand Magister';
    }

    public function source(): string
    {
        return 'Raid (The Reckoning)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'grand_magister', 'name' => 'Grand Magister'],
        ];
    }
}
