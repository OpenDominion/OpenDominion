<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class LichKing extends AbstractEnemy
{
    public function key(): string
    {
        return 'lich_king';
    }

    public function name(): string
    {
        return 'The Lich King';
    }

    public function stats(): array
    {
        return [
            'health' => 150,
            'attack' => 30,
            'defense' => 30,
            'evasion' => 25,
            'focus' => 10,
            'counter' => 15,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return ['enrage' => []];
    }

    public function ai(): string
    {
        return 'aggressive';
    }
}
