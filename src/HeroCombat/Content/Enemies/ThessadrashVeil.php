<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class ThessadrashVeil extends AbstractEnemy
{
    public function key(): string
    {
        return 'thessadrash_veil';
    }

    public function name(): string
    {
        return 'Veil of Thessadrash';
    }

    public function stats(): array
    {
        return [
            'health' => 100,
            'attack' => 30,
            'defense' => 10,
            'evasion' => 0,
            'focus' => 15,
            'counter' => 10,
            'recover' => 15,
        ];
    }

    public function effects(): array
    {
        return [
            'aspect_shift' => ['next' => 'thessadrash_shadow', 'name' => 'Shadow of Thessadrash'],
            'enrage' => [],
        ];
    }

    public function ai(): string
    {
        return 'aggressive';
    }
}
