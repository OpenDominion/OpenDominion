<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class AurelisDefender extends AbstractEnemy
{
    public function key(): string
    {
        return 'aurelis_defender';
    }

    public function name(): string
    {
        return 'Aurelis Defender';
    }

    public function stats(): array
    {
        return [
            'health' => 45,
            'attack' => 25,
            'defense' => 15,
            'evasion' => 5,
            'focus' => 5,
            'counter' => 5,
            'recover' => 5,
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
