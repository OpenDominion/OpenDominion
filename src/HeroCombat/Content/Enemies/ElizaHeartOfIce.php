<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class ElizaHeartOfIce extends AbstractEnemy
{
    public function key(): string
    {
        return 'eliza_heart_of_ice';
    }

    public function name(): string
    {
        return 'Eliza, the Heart of Ice';
    }

    public function stats(): array
    {
        return [
            'health' => 200,
            'attack' => 35,
            'defense' => 15,
            'evasion' => 25,
            'focus' => 20,
            'counter' => 15,
            'recover' => 10,
        ];
    }

    public function effects(): array
    {
        return ['snow_witch_curse' => [], 'frostbite' => []];
    }

    public function ai(): string
    {
        return 'wraith';
    }
}
