<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class ThessadrashShadow extends AbstractEnemy
{
    public function key(): string
    {
        return 'thessadrash_shadow';
    }

    public function name(): string
    {
        return 'Shadow of Thessadrash';
    }

    public function stats(): array
    {
        return [
            'health' => 100,
            'attack' => 25,
            'defense' => 10,
            'evasion' => 50,
            'focus' => 10,
            'counter' => 20,
            'recover' => 20,
        ];
    }

    public function effects(): array
    {
        return [
            'aspect_shift' => ['next' => 'thessadrash_maw', 'name' => 'Maw of Thessadrash'],
            'elusive' => [],
        ];
    }

    public function ai(): string
    {
        return 'wraith';
    }
}
