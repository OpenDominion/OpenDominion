<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

class Attack extends AbstractAttack
{
    public function key(): string
    {
        return 'attack';
    }

    public function name(): string
    {
        return 'Attack';
    }

    public function description(): string
    {
        return 'Deals damage equal to your attack minus the target\'s defense.';
    }
}
