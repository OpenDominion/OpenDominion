<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class SkeletonWarrior extends AbstractEnemy
{
    public function key(): string
    {
        return 'skeleton_warrior';
    }

    public function name(): string
    {
        return 'Skeleton Warrior';
    }

    public function stats(): array
    {
        return [
            'health' => 40,
            'attack' => 28,
            'defense' => 20,
            'evasion' => 0,
            'focus' => 0,
            'counter' => 0,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return ['undying' => []];
    }

    public function ai(): string
    {
        return 'attack';
    }
}
