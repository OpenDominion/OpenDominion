<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Defend extends AbstractAbility
{
    public function key(): string
    {
        return 'defend';
    }

    public function name(): string
    {
        return 'Defend';
    }

    public function description(): string
    {
        return 'Doubles your defense this turn.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function tags(): array
    {
        return [CombatTag::Stance];
    }

    public function onDeclare(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'defending', 1);
    }

    public function resolve(ActionContext $context): void
    {
        $context->say('defend');
    }

    public function messages(): array
    {
        return ['defend' => '{actor} takes a defensive stance.'];
    }
}
