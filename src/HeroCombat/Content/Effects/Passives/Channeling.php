<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Lets Focused stack without limit (see Focused::maxStacks and Focus::canUse).
 */
class Channeling extends AbstractPassive
{
    public function key(): string
    {
        return 'channeling';
    }

    public function name(): string
    {
        return 'Channeling';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Focus can be used while already active, stacking bonus damage.';
    }
}
