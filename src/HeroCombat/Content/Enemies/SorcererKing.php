<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class SorcererKing extends AbstractEnemy
{
    public function key(): string
    {
        return 'sorcerer_king';
    }

    public function name(): string
    {
        return 'The Sorcerer King';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 20,
            'defense' => 20,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 10,
            'recover' => 30,
        ];
    }

    public function effects(): array
    {
        return ['undying' => []];
    }

    public function ai(): string
    {
        return 'defensive';
    }
}
