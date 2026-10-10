<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class AdmiralVaros extends AbstractEnemy
{
    public function key(): string
    {
        return 'admiral_varos';
    }

    public function name(): string
    {
        return 'Admiral Varos';
    }

    public function stats(): array
    {
        return [
            'health' => 180,
            'attack' => 30,
            'defense' => 15,
            'evasion' => 15,
            'focus' => 15,
            'counter' => 15,
            'recover' => 15,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['blade_flurry'];
    }

    public function effects(): array
    {
        return ['admirals_orders' => []];
    }

    public function ai(): string
    {
        return 'pirate';
    }
}
