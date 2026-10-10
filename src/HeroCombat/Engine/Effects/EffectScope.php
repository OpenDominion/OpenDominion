<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

enum EffectScope: string
{
    case Combatant = 'combatant';
    case Team = 'team';
    case Field = 'field';
}
