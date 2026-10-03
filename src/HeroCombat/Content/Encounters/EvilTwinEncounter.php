<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class EvilTwinEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'default';
    }

    public function name(): string
    {
        return 'Evil Twin';
    }

    public function source(): string
    {
        return 'Practice';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'evil_twin', 'name' => 'Evil Twin', 'stats' => $context->leaderStats],
        ];
    }
}
