<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Summons one numbered minion onto the caster's team, e.g. "Skeleton Warrior #2".
 */
abstract class AbstractSummon extends AbstractBossMove
{
    abstract protected function template(): string;

    public function targetRule(): TargetRule
    {
        return TargetRule::Self;
    }

    public function tags(): array
    {
        return [CombatTag::Summon];
    }

    public function resolve(ActionContext $context): void
    {
        $number = count($context->battle->withTemplate($this->template(), livingOnly: false)) + 1;
        $name = $context->battle->registry->enemy($this->template())->name();

        $context->summon($this->template(), "{$name} #{$number}");
        $context->say('summon');
    }
}
