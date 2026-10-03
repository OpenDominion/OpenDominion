<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Mending extends AbstractPassive
{
    public function key(): string
    {
        return 'mending';
    }

    public function name(): string
    {
        return 'Mending';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Focus enhances your Recover ability, increasing healing.';
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        if (!$battle->hasTag($subject, CombatTag::Focused)) {
            return [];
        }

        return [Modifier::flat(Stat::Recover, $subject->baseStat(Stat::Focus))];
    }

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void
    {
        if ($context->actor->id === $instance->ownerId && in_array(CombatTag::Heal, $context->ability->tags(), true)) {
            $focused = $battle->effects->find($context->actor, 'focused');
            if ($focused !== null) {
                $battle->effects->remove($focused);
            }
        }
    }
}
