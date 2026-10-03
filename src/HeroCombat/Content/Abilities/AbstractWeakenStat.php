<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Content\AbstractAbility;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Applies a stacking stat debuff to an enemy, unless the stat is already at its floor.
 */
abstract class AbstractWeakenStat extends AbstractAbility
{
    public const STAT_FLOOR = 5;

    abstract protected function stat(): Stat;

    abstract protected function effectKey(): string;

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        if ($context->battle->stat($target, $this->stat()) <= self::STAT_FLOOR) {
            $context->say('no_effect');
            return;
        }

        $context->applyEffect($target, $this->effectKey());
        $context->say('weaken');
    }

    public function messages(): array
    {
        return ['no_effect' => '{actor} uses ' . $this->name() . ', but it has no effect.'];
    }
}
