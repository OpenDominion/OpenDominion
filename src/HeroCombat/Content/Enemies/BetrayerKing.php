<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class BetrayerKing extends AbstractEnemy
{
    public function key(): string
    {
        return 'betrayer_king';
    }

    public function name(): string
    {
        return 'The Betrayer King';
    }

    public function stats(): array
    {
        return [
            'health' => 80,
            'attack' => 25,
            'defense' => 15,
            'evasion' => 25,
            'focus' => 10,
            'counter' => 15,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return ['undying' => []];
    }
}
