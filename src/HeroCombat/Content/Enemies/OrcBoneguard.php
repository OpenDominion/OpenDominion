<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class OrcBoneguard extends AbstractEnemy
{
    public function key(): string
    {
        return 'orc_boneguard';
    }

    public function name(): string
    {
        return 'Orc Boneguard';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 30,
            'defense' => 10,
            'evasion' => 0,
            'focus' => 0,
            'counter' => 10,
            'recover' => 0,
        ];
    }

    public function effects(): array
    {
        return [
            'soul_tribute' => [
                'target' => 'dreadsoul',
                'bonuses' => ['attack' => 10, 'defense' => 5],
            ],
        ];
    }

    public function ai(): string
    {
        return 'attack';
    }
}
