<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class RebelCorsairEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'rebel_corsair';
    }

    public function name(): string
    {
        return 'Rebel Corsairs';
    }

    public function source(): string
    {
        return 'Raid (The Island Fortress)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'rebel_corsair', 'name' => 'Rebel Corsair #1'],
            ['template' => 'rebel_corsair', 'name' => 'Rebel Corsair #2'],
            ['template' => 'rebel_corsair', 'name' => 'Rebel Corsair #3'],
        ];
    }
}
