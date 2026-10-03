<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class Elusive extends AbstractPassive
{
    public function key(): string
    {
        return 'elusive';
    }

    public function name(): string
    {
        return 'Elusive';
    }

    public function description(EffectInstance $instance): string
    {
        return 'When evading a non-focused attack, damage is reduced to 0 instead of half.';
    }

    public function onEvaded(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if (!$damage->attackerFocused) {
            $damage->evadeMultiplier = 0;
        }
    }
}
