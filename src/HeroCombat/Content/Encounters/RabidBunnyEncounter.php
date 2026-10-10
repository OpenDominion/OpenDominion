<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class RabidBunnyEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'rabid_bunny';
    }

    public function name(): string
    {
        return 'Rabid Bunny';
    }

    public function source(): string
    {
        return 'Seasonal Battle (Round 44)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'rabid_bunny', 'name' => 'Rabid Bunny'],
        ];
    }
}
