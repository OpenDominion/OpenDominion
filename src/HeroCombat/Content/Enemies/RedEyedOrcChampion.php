<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

/**
 * Ullyaoth's revived champion. Undying brings it back whether heroes defeat it or Ullyaoth
 * devours it.
 */
class RedEyedOrcChampion extends AbstractEnemy
{
    public function key(): string
    {
        return 'red_eyed_orc_champion';
    }

    public function name(): string
    {
        return 'Red-Eyed Orc Champion';
    }

    public function stats(): array
    {
        return [
            'health' => 80,
            'attack' => 25,
            'defense' => 15,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 10,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return ['undying' => []];
    }

    public function ai(): string
    {
        return 'orc_champion';
    }
}
