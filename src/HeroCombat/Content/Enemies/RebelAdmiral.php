<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class RebelAdmiral extends AbstractEnemy
{
    public function key(): string
    {
        return 'rebel_admiral';
    }

    public function name(): string
    {
        return 'Rebel Admiral';
    }

    public function stats(): array
    {
        return [
            'health' => 150,
            'attack' => 40,
            'defense' => 25,
            'evasion' => 0,
            'focus' => 10,
            'counter' => 10,
            'recover' => 20,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['blade_flurry'];
    }

    public function effects(): array
    {
        return ['enrage' => []];
    }

    public function ai(): string
    {
        return 'pirate';
    }
}
