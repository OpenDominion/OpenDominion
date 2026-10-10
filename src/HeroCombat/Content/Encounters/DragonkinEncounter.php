<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class DragonkinEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'dragonkin';
    }

    public function name(): string
    {
        return 'Dragonkin';
    }

    public function source(): string
    {
        return 'Raid (Lair of the Dragon)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'dragonkin', 'name' => 'Dragonkin #1'],
            ['template' => 'dragonkin', 'name' => 'Dragonkin #2'],
            ['template' => 'dragonkin', 'name' => 'Dragonkin #3'],
        ];
    }
}
