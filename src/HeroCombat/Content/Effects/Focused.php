<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\ExpiryTiming;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;

/**
 * Adds focus (per stack) to the next attack. Consumed by any Attack-tagged ability.
 */
class Focused extends AbstractEffect
{
    public function key(): string
    {
        return 'focused';
    }

    public function name(): string
    {
        return 'Focused';
    }

    public function description(EffectInstance $instance): string
    {
        return $instance->stacks > 1
            ? "Next attack adds focus x{$instance->stacks}."
            : 'Next attack adds focus.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Stack;
    }

    public function maxStacks(Battle $battle, ?CombatantState $owner): ?int
    {
        return $owner !== null && $battle->effects->has($owner, 'channeling') ? null : 1;
    }

    public function expiry(): ExpiryTiming
    {
        return ExpiryTiming::OnConsume;
    }

    public function tags(): array
    {
        return [CombatTag::Focused];
    }

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void
    {
        if ($context->actor->id === $instance->ownerId && in_array(CombatTag::Attack, $context->ability->tags(), true)) {
            $battle->effects->remove($instance);
        }
    }
}
