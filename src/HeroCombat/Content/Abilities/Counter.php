<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Counter extends AbstractAbility
{
    public function key(): string
    {
        return 'counter';
    }

    public function name(): string
    {
        return 'Counter';
    }

    public function description(): string
    {
        return 'If attacked this turn, strike back for attack plus counter damage.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function tags(): array
    {
        return [CombatTag::Stance];
    }

    public function onDeclare(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'countering', 1);
    }

    public function resolve(ActionContext $context): void
    {
        $context->say('counter');
    }

    public function messages(): array
    {
        return ['counter' => '{actor} prepares to counter-attack.'];
    }
}
