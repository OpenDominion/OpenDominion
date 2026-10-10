<?php

namespace OpenDominion\HeroCombat\Engine\Stats;

enum Stat: string
{
    case Health = 'health';
    case Attack = 'attack';
    case Defense = 'defense';
    case Evasion = 'evasion';
    case Focus = 'focus';
    case Counter = 'counter';
    case Recover = 'recover';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
