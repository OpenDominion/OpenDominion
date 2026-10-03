<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class HeartOfIceEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'heart_of_ice';
    }

    public function name(): string
    {
        return 'Eliza, the Heart of Ice';
    }

    public function source(): string
    {
        return 'Raid (Heart of Ice)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'eliza_heart_of_ice', 'name' => 'Eliza, the Heart of Ice'],
        ];
    }
}
