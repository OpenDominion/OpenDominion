<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class RabidBunny extends AbstractEnemy
{
    public function key(): string
    {
        return 'rabid_bunny';
    }

    public function name(): string
    {
        return 'Rabid Bunny';
    }

    public function stats(): array
    {
        return [
            'health' => 100,
            'attack' => 40,
            'defense' => 20,
            'evasion' => 50,
            'focus' => 10,
            'counter' => 10,
            'recover' => 40,
        ];
    }
}
