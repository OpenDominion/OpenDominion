<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class AdmiralVarosEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'admiral_varos';
    }

    public function name(): string
    {
        return 'Admiral Varos';
    }

    public function source(): string
    {
        return 'Raid (The Tide of Vethara)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'admiral_varos', 'name' => 'Admiral Varos'],
        ];
    }
}
