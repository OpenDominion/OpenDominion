<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class Dragonkin extends AbstractEnemy
{
    public function key(): string
    {
        return 'dragonkin';
    }

    public function name(): string
    {
        return 'Dragonkin';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 40,
            'defense' => 10,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 10,
            'recover' => 20,
        ];
    }
}
