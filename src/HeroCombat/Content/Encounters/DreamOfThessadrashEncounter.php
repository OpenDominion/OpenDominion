<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class DreamOfThessadrashEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'dream_of_thessadrash';
    }

    public function name(): string
    {
        return 'The Dream of Thessadrash';
    }

    public function source(): string
    {
        return 'Raid (Omen of Fire)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'thessadrash_veil', 'name' => 'Veil of Thessadrash'],
        ];
    }
}
