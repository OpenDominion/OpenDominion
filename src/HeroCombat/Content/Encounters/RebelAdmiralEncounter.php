<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class RebelAdmiralEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'rebel_admiral';
    }

    public function name(): string
    {
        return 'Rebel Admiral';
    }

    public function source(): string
    {
        return 'Raid (The Island Fortress)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'rebel_admiral', 'name' => 'Rebel Admiral'],
        ];
    }
}
