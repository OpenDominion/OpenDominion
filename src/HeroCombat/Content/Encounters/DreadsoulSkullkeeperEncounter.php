<?php

namespace OpenDominion\HeroCombat\Content\Encounters;

use OpenDominion\HeroCombat\Content\AbstractEncounter;
use OpenDominion\HeroCombat\Content\Enemies\DreadsoulSkullkeeper;
use OpenDominion\HeroCombat\Engine\EncounterContext;

/**
 * Each prior realm victory wounds the Skullkeeper by 2% health, to half at most.
 */
class DreadsoulSkullkeeperEncounter extends AbstractEncounter
{
    public const WOUND_PER_WIN = 0.02;
    public const MAX_WOUND = 0.5;

    public function key(): string
    {
        return 'dreadsoul_skullkeeper';
    }

    public function name(): string
    {
        return 'Dreadsoul Skullkeeper';
    }

    public function source(): string
    {
        return 'Raid (Siege of Emberveil)';
    }

    public function roster(EncounterContext $context): array
    {
        $boss = ['template' => 'dreadsoul', 'name' => 'Dreadsoul Skullkeeper'];

        if ($context->priorWins > 0) {
            $multiplier = max(1 - self::MAX_WOUND, 1 - $context->priorWins * self::WOUND_PER_WIN);
            $boss['stats'] = ['health' => max(1, (int) round((new DreadsoulSkullkeeper())->stats()['health'] * $multiplier))];
        }

        return [
            $boss,
            ['template' => 'orc_boneguard', 'name' => 'Orc Boneguard'],
            ['template' => 'orc_hexcaller', 'name' => 'Orc Hexcaller'],
        ];
    }
}
