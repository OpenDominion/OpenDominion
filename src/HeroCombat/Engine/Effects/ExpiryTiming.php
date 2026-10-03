<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

enum ExpiryTiming: string
{
    /** Remaining turns count down at the end of every turn. */
    case TurnEnd = 'turn_end';
    /** Remaining turns count down after the owner resolves an action. */
    case OwnerActionEnd = 'owner_action_end';
    /** Removed as soon as the owner takes damage (or its duration runs out at turn end). */
    case OnDamageTaken = 'on_damage_taken';
    /** Never counts down; removed only when consumed or dispelled. */
    case OnConsume = 'on_consume';
}
