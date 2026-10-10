<?php

namespace OpenDominion\HeroCombat\Engine\Events;

enum EventType: string
{
    case Selected = 'selected';
    case Damage = 'damage';
    case Heal = 'heal';
    case EffectApplied = 'effect_applied';
    case EffectRemoved = 'effect_removed';
    case Summoned = 'summoned';
    case Died = 'died';
    case Revived = 'revived';
    case Fizzled = 'fizzled';
}
