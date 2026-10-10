<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class GateWardenEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'gate_warden';
    }

    public function name(): string
    {
        return 'Gate Warden';
    }

    public function source(): string
    {
        return 'Raid (Ironhold Citadel)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'gate_warden', 'name' => 'Gate Warden'],
        ];
    }
}
