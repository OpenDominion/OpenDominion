<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

class Cleanse extends AbstractAbility
{
    public function key(): string
    {
        return 'cleanse';
    }

    public function name(): string
    {
        return 'Cleanse';
    }

    public function description(): string
    {
        return 'Calls upon stored mana reserves to remove curses.';
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
        $context->removeEffectsTagged($context->actor, CombatTag::Curse);
        $context->say('cleanse');
    }

    public function messages(): array
    {
        return ['cleanse' => '{actor} draws on the well of silver light.'];
    }
}
