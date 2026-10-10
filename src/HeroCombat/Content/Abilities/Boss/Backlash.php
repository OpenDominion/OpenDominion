<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Backlash extends AbstractAbility
{
    public function key(): string
    {
        return 'backlash';
    }

    public function name(): string
    {
        return 'Backlash';
    }

    public function description(): string
    {
        return 'Attackers take half the damage they deal for the next two turns.';
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
        return !$battle->effects->has($actor, 'backlash');
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'backlash');
        $context->say('backlash');
    }

    public function messages(): array
    {
        return ['backlash' => 'Crackling energy coils around {actor}, eager to strike back at anyone who lands a blow.'];
    }
}
