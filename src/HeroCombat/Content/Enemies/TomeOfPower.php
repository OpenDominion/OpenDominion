<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class TomeOfPower extends AbstractEnemy
{
    public function key(): string
    {
        return 'tome_of_power';
    }

    public function name(): string
    {
        return 'Tome of Power';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 25,
            'defense' => 30,
            'evasion' => 50,
            'focus' => 0,
            'counter' => 10,
            'recover' => 10,
        ];
    }

    public function effects(): array
    {
        return [
            'tome_of_power' => [],
            'power_source' => [
                'target' => 'lich_king',
                'reductions' => ['defense' => 10, 'evasion' => 25],
            ],
        ];
    }

    public function ai(): string
    {
        return 'defensive';
    }
}
