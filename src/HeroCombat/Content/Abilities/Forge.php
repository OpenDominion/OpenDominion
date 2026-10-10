<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Forge extends AbstractAbility
{
    public function key(): string
    {
        return 'forge';
    }

    public function name(): string
    {
        return 'Forge';
    }

    public function description(): string
    {
        return 'Increases attack value by 1 for the remainder of the battle.';
    }

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function resolve(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'forged');
        $context->say('forge');
    }

    public function messages(): array
    {
        return ['forge' => '{actor} increases attack value by 1.'];
    }
}
