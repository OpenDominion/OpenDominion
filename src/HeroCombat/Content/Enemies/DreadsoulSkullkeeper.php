<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class DreadsoulSkullkeeper extends AbstractEnemy
{
    public function key(): string
    {
        return 'dreadsoul';
    }

    public function name(): string
    {
        return 'Dreadsoul Skullkeeper';
    }

    public function stats(): array
    {
        return [
            'health' => 150,
            'attack' => 25,
            'defense' => 15,
            'evasion' => 15,
            'focus' => 10,
            'counter' => 30,
            'recover' => 10,
        ];
    }

    public function effects(): array
    {
        return ['soul_harvest' => []];
    }

    public function ai(): string
    {
        return 'warchief';
    }
}
