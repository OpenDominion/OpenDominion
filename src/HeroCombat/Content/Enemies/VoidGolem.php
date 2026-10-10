<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class VoidGolem extends AbstractEnemy
{
    public function key(): string
    {
        return 'golem';
    }

    public function name(): string
    {
        return 'Void Golem';
    }

    public function stats(): array
    {
        return [
            'health' => 50,
            'attack' => 25,
            'defense' => 20,
            'evasion' => 20,
            'focus' => 0,
            'counter' => 10,
            'recover' => 0,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['fortify'];
    }

    public function effects(): array
    {
        return ['hardiness' => []];
    }

    public function ai(): string
    {
        return 'fortify';
    }
}
