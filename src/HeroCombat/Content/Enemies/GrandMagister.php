<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class GrandMagister extends AbstractEnemy
{
    public function key(): string
    {
        return 'grand_magister';
    }

    public function name(): string
    {
        return 'Grand Magister';
    }

    public function stats(): array
    {
        return [
            'health' => 200,
            'attack' => 40,
            'defense' => 20,
            'evasion' => 10,
            'focus' => 20,
            'counter' => 0,
            'recover' => 20,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['magic_ward', 'backlash', 'silence', 'arcane_conduit'];
    }

    public function ai(): string
    {
        return 'grand_magister';
    }
}
