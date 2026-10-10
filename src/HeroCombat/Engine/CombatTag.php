<?php

namespace OpenDominion\HeroCombat\Engine;

/**
 * Tags are the gating vocabulary of the engine. Effects grant tags to their holder,
 * abilities declare tags that block or are required for their use, and effects
 * can declare immunity to tags.
 */
enum CombatTag: string
{
    case Attack = 'attack';
    case AreaOfEffect = 'area_of_effect';
    case Heal = 'heal';
    case Stance = 'stance';
    case Summon = 'summon';
    case Defending = 'defending';
    case Countering = 'countering';
    case Recovering = 'recovering';
    case Focused = 'focused';
    case Shielded = 'shielded';
    case Curse = 'curse';
    case Frost = 'frost';
    case Frozen = 'frozen';
    case Stunned = 'stunned';
    case Silenced = 'silenced';
    case Provoked = 'provoked';
    case Telegraph = 'telegraph';
    case Aura = 'aura';
}
