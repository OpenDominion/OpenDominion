<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Lowers the chance that Confused replaces the holder's actions (read by DragonsMiasma).
 */
class ClearMinded extends AbstractPassive
{
    public const CONFUSION_CHANCE = 0.25;

    public function key(): string
    {
        return 'clear_minded';
    }

    public function name(): string
    {
        return 'Clear-Minded';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Trained in the arcane, this hero is harder to confuse.';
    }
}
