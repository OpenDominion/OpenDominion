<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class Planewalker extends AbstractEnemy
{
    public function key(): string
    {
        return 'planewalker';
    }

    public function name(): string
    {
        return 'The Planewalker';
    }

    public function stats(): array
    {
        return [
            'health' => 200,
            'attack' => 40,
            'defense' => 10,
            'evasion' => 50,
            'focus' => 20,
            'counter' => 20,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return [
            'elusive' => [],
            'void_rift' => [],
            'wounded_retreat' => [],
        ];
    }
}
