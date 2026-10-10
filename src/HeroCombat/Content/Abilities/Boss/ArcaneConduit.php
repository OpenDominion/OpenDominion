<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class ArcaneConduit extends AbstractAbility
{
    public function key(): string
    {
        return 'arcane_conduit';
    }

    public function name(): string
    {
        return 'Arcane Conduit';
    }

    public function description(): string
    {
        return 'Attacks next turn for double damage.';
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
        return !$battle->effects->has($actor, 'arcane_conduit');
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'arcane_conduit');
        $context->say('conduit');
    }

    public function messages(): array
    {
        return ['conduit' => 'Power floods into {actor}\'s staff, which burns brighter with every breath.'];
    }
}
