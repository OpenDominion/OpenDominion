<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class GateWarden extends AbstractEnemy
{
    public function key(): string
    {
        return 'gate_warden';
    }

    public function name(): string
    {
        return 'Gate Warden';
    }

    public function stats(): array
    {
        return [
            'health' => 150,
            'attack' => 40,
            'defense' => 25,
            'evasion' => 10,
            'focus' => 10,
            'counter' => 50,
            'recover' => 20,
        ];
    }

    public function ai(): string
    {
        return 'counter';
    }
}
