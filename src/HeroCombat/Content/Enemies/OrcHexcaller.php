<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class OrcHexcaller extends AbstractEnemy
{
    public function key(): string
    {
        return 'orc_hexcaller';
    }

    public function name(): string
    {
        return 'Orc Hexcaller';
    }

    public function stats(): array
    {
        return [
            'health' => 50,
            'attack' => 25,
            'defense' => 10,
            'evasion' => 15,
            'focus' => 0,
            'counter' => 0,
            'recover' => 10,
        ];
    }

    public function effects(): array
    {
        return [
            'soul_tribute' => [
                'target' => 'dreadsoul',
                'bonuses' => ['attack' => 10, 'defense' => 5],
            ],
        ];
    }

    public function ai(): string
    {
        return 'aggressive';
    }
}
