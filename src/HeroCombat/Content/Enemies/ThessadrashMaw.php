<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class ThessadrashMaw extends AbstractEnemy
{
    public function key(): string
    {
        return 'thessadrash_maw';
    }

    public function name(): string
    {
        return 'Maw of Thessadrash';
    }

    public function stats(): array
    {
        return [
            'health' => 125,
            'attack' => 25,
            'defense' => 25,
            'evasion' => 0,
            'focus' => 0,
            'counter' => 30,
            'recover' => 20,
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
