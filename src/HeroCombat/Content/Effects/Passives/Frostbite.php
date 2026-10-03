<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Landed attacks against heroes add a stack of Frostbitten (-1 defense each).
 */
class Frostbite extends AbstractPassive
{
    public function key(): string
    {
        return 'frostbite';
    }

    public function name(): string
    {
        return 'Frostbite';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Each landed attack reduces the target\'s defense value by 1 for the remainder of the battle.';
    }

    public function afterDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $target = $damage->target;
        if ($damage->request->isCounter || !$damage->request->hasTag(CombatTag::Attack) || !$target->isHuman()) {
            return;
        }

        $applied = $battle->effects->apply($target, 'frostbitten', null, 1, [], $damage->attacker());
        if ($applied !== null) {
            $battle->say($damage->attacker(), "Frostbite creeps into {$target->name} (stack {$applied->stacks}).");
        }
    }
}
