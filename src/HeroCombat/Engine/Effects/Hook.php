<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

/**
 * Every point in the turn where effects may react. Values match the method names on
 * the Effect contract so the dispatcher can invoke them directly.
 */
enum Hook: string
{
    case TurnStart = 'onTurnStart';
    case TurnEnd = 'onTurnEnd';
    case ForcedIntent = 'forcedIntent';
    case BeforeDamageDealt = 'beforeDamageDealt';
    case RedirectDamage = 'redirectDamage';
    case Evaded = 'onEvaded';
    case BeforeDamageTaken = 'beforeDamageTaken';
    case LethalDamage = 'onLethalDamage';
    case AfterDamageTaken = 'afterDamageTaken';
    case AfterDamageDealt = 'afterDamageDealt';
    case AbilityUsed = 'onAbilityUsed';
    case Death = 'onDeath';
    case AnyDeath = 'onAnyDeath';
}
