<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class FallenKingsEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'fallen_kings';
    }

    public function name(): string
    {
        return 'The Fallen Kings';
    }

    public function source(): string
    {
        return 'Raid (The Tomb of Kings)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'betrayer_king', 'name' => 'The Betrayer King'],
            ['template' => 'sorcerer_king', 'name' => 'The Sorcerer King'],
            ['template' => 'warrior_king', 'name' => 'The Warrior King'],
        ];
    }
}
