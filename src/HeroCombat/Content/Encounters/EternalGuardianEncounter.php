<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class EternalGuardianEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'eternal_guardian';
    }

    public function name(): string
    {
        return 'The Guardian of the Throne';
    }

    public function source(): string
    {
        return 'Raid (The Tomb of Kings)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'eternal_guardian', 'name' => 'The Eternal Guardian'],
        ];
    }
}
