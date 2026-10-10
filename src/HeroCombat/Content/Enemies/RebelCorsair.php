<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class RebelCorsair extends AbstractEnemy
{
    public function key(): string
    {
        return 'rebel_corsair';
    }

    public function name(): string
    {
        return 'Rebel Corsair';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 35,
            'defense' => 20,
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

    public function ai(): string
    {
        return 'pirate';
    }
}
