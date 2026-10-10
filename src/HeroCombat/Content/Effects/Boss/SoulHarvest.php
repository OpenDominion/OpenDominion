<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Descriptive trait: the strength itself arrives through each minion's Soul Tribute.
 */
class SoulHarvest extends AbstractPassive
{
    public function key(): string
    {
        return 'soul_harvest';
    }

    public function name(): string
    {
        return 'Soul Harvest';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Absorbs the strength of fallen allies, growing more powerful with each death.';
    }
}
