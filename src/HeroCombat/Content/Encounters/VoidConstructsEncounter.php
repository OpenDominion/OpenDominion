<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class VoidConstructsEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'planewalker_golems';
    }

    public function name(): string
    {
        return 'Void Constructs';
    }

    public function source(): string
    {
        return 'Raid (Planewalker Incursion)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'golem', 'name' => 'Golem #1'],
            ['template' => 'golem', 'name' => 'Golem #2'],
            ['template' => 'golem', 'name' => 'Golem #3'],
        ];
    }
}
