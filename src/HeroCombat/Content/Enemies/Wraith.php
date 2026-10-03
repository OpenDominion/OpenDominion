<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class Wraith extends AbstractEnemy
{
    public function key(): string
    {
        return 'wraith';
    }

    public function name(): string
    {
        return 'The Wraith';
    }

    public function stats(): array
    {
        return [
            'health' => 100,
            'attack' => 35,
            'defense' => 15,
            'evasion' => 25,
            'focus' => 15,
            'counter' => 15,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return ['soul_rend_charge' => [], 'elusive' => []];
    }

    public function ai(): string
    {
        return 'wraith';
    }
}
