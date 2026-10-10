<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class NightbringerEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'nightbringer';
    }

    public function name(): string
    {
        return 'The Nightbringer';
    }

    public function source(): string
    {
        return 'Raid (Rise of the Nightbringer)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'nightbringer', 'name' => 'The Nightbringer'],
            ['template' => 'nox_cultist', 'name' => 'Nox Cultist #1'],
            ['template' => 'nox_cultist', 'name' => 'Nox Cultist #2'],
        ];
    }
}
