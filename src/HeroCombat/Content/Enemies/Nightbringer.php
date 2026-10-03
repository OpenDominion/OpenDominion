<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class Nightbringer extends AbstractEnemy
{
    public function key(): string
    {
        return 'nightbringer';
    }

    public function name(): string
    {
        return 'The Nightbringer';
    }

    public function stats(): array
    {
        return [
            'health' => 200,
            'attack' => 50,
            'defense' => 20,
            'evasion' => 0,
            'focus' => 0,
            'counter' => 20,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return ['elusive' => [], 'nightfall' => []];
    }

    public function ai(): string
    {
        return 'counter';
    }
}
