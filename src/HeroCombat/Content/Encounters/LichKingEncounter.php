<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Engine\EncounterContext;

class LichKingEncounter extends AbstractEncounter
{
    public function key(): string
    {
        return 'lich_king';
    }

    public function name(): string
    {
        return 'The Lich King';
    }

    public function source(): string
    {
        return 'Raid (The Lich King\'s Fury)';
    }

    public function roster(EncounterContext $context): array
    {
        return [
            ['template' => 'lich_king', 'name' => 'Lich King'],
            ['template' => 'tome_of_power', 'name' => 'Tome of Power'],
        ];
    }
}
