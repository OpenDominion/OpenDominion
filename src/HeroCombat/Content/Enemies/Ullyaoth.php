<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class Ullyaoth extends AbstractEnemy
{
    public function key(): string
    {
        return 'ullyaoth';
    }

    public function name(): string
    {
        return 'World-Eater Ullyaoth';
    }

    public function stats(): array
    {
        return [
            'health' => 300,
            'attack' => 25,
            'defense' => 30,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 10,
            'recover' => 0,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['hemorrhage', 'devour', 'corpseblood_feast'];
    }

    public function effects(): array
    {
        return ['blood_rain' => ['champion' => 'bloodbound_orc']];
    }

    public function ai(): string
    {
        return 'ullyaoth';
    }
}
