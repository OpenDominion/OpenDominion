<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class RexLunae extends AbstractEnemy
{
    public function key(): string
    {
        return 'rex_lunae';
    }

    public function name(): string
    {
        return 'Rex Lunae';
    }

    public function stats(): array
    {
        return [
            'health' => 160,
            'attack' => 35,
            'defense' => 25,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 15,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return ['hungering_moon_curse' => []];
    }

    public function ai(): string
    {
        return 'noctis';
    }
}
