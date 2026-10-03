<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class EternalGuardian extends AbstractEnemy
{
    public function key(): string
    {
        return 'eternal_guardian';
    }

    public function name(): string
    {
        return 'The Eternal Guardian';
    }

    public function stats(): array
    {
        return [
            'health' => 90,
            'attack' => 30,
            'defense' => 20,
            'evasion' => 0,
            'focus' => 0,
            'counter' => 0,
            'recover' => 10,
        ];
    }

    public function effects(): array
    {
        return ['undying_legion' => [], 'necromancy' => []];
    }

    public function ai(): string
    {
        return 'summoner';
    }
}
