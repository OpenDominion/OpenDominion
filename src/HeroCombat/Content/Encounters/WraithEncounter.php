<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class WraithEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'wraith';
    }

    public function name(): string
    {
        return 'The Wraith';
    }

    public function source(): string
    {
        return 'Raid (The Wraith)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'wraith', 'name' => 'The Wraith'],
        ];
    }
}
