<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Recover extends AbstractAbility
{
    public function key(): string
    {
        return 'recover';
    }

    public function name(): string
    {
        return 'Recover';
    }

    public function description(): string
    {
        return 'Heals damage equal to your recover value, but reduces your defense by 5 this turn.';
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
        return [CombatTag::Heal];
    }

    public function blockedByTags(): array
    {
        return [CombatTag::Stunned, CombatTag::Silenced];
    }

    public function onDeclare(ActionContext $context): void
    {
        $context->applyEffect($context->actor, 'recovering', 1);
    }

    public function resolve(ActionContext $context): void
    {
        $amount = $context->battle->stat($context->actor, Stat::Recover);
        $healed = $context->heal($context->actor, $amount);
        $context->say('recover', ['amount' => $healed]);
    }

    public function messages(): array
    {
        return ['recover' => '{actor} recovers {amount} health.'];
    }
}
