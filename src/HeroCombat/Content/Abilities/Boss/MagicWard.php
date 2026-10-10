<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class MagicWard extends AbstractAbility
{
    public function key(): string
    {
        return 'magic_ward';
    }

    public function name(): string
    {
        return 'Magic Ward';
    }

    public function description(): string
    {
        return 'Halves damage taken for the next three turns.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 3;
    }

    public function canUse(CombatantState $actor, Battle $battle): bool
    {
        return !$battle->effects->has($actor, 'magic_ward');
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'magic_ward');
        $context->say('ward');
    }

    public function messages(): array
    {
        return ['ward' => '{actor} traces a circle of glowing runes, and a shimmering ward settles around them.'];
    }
}
