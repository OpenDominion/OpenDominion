<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

enum EffectKind: string
{
    case Buff = 'buff';
    case Debuff = 'debuff';
    case Status = 'status';
    case Innate = 'innate';
}
