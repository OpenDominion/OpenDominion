<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class WarriorKing extends AbstractEnemy
{
    public function key(): string
    {
        return 'warrior_king';
    }

    public function name(): string
    {
        return 'The Warrior King';
    }

    public function stats(): array
    {
        return [
            'health' => 80,
            'attack' => 30,
            'defense' => 10,
            'evasion' => 10,
            'focus' => 20,
            'counter' => 10,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return ['undying' => []];
    }

    public function ai(): string
    {
        return 'aggressive';
    }
}
