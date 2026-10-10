<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\Hook;

class Hardiness extends AbstractPassive
{
    public function key(): string
    {
        return 'hardiness';
    }

    public function name(): string
    {
        return 'Hardiness';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Remain on 1 health the first time your health would be reduced below 1.';
    }

    public function handlerPriority(Hook $hook): int
    {
        return -10;
    }

    public function onLethalDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): bool
    {
        $target = $damage->target;
        $damage->amount = max(0, $target->currentHealth - 1);
        $battle->effects->remove($instance);
        $battle->say($target, "{$target->name} clings to life with 1 health.");

        return true;
    }
}
