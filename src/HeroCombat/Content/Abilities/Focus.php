<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Focus extends AbstractAbility
{
    public function key(): string
    {
        return 'focus';
    }

    public function name(): string
    {
        return 'Focus';
    }

    public function description(): string
    {
        return 'Your next attack adds your focus value to its damage.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function blockedByTags(): array
    {
        return [CombatTag::Stunned, CombatTag::Silenced];
    }

    public function canUse(CombatantState $actor, Battle $battle): bool
    {
        return !$battle->hasTag($actor, CombatTag::Focused) || $battle->effects->has($actor, 'channeling');
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'focused');
        $context->say('focus');
    }

    public function messages(): array
    {
        return ['focus' => '{actor} focuses their energy for the next attack.'];
    }
}
