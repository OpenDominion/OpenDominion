<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class NoxCultist extends AbstractEnemy
{
    public function key(): string
    {
        return 'nox_cultist';
    }

    public function name(): string
    {
        return 'Nox Cultist';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 25,
            'defense' => 15,
            'evasion' => 0,
            'focus' => 5,
            'counter' => 10,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return ['dying_light' => ['target' => 'nightbringer']];
    }

    public function ai(): string
    {
        return 'aggressive';
    }
}
