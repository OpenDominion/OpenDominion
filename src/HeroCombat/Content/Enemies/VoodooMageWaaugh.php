<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

class VoodooMageWaaugh extends AbstractEnemy
{
    public function key(): string
    {
        return 'voodoo_mage_waaugh';
    }

    public function name(): string
    {
        return 'Voodoo Mage Waaugh';
    }

    public function stats(): array
    {
        return [
            'health' => 160,
            'attack' => 30,
            'defense' => 15,
            'evasion' => 10,
            'focus' => 10,
            'counter' => 10,
            'recover' => 10,
        ];
    }

    protected function extraAbilities(): array
    {
        return ['hexfire'];
    }

    public function effects(): array
    {
        return ['dragons_miasma' => []];
    }

    public function ai(): string
    {
        return 'waaugh';
    }
}
