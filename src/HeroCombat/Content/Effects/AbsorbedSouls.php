<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;

/**
 * Battle-long stacks gained from Bone Splinter. Each adds damage to the owner's spells.
 */
class AbsorbedSouls extends AbstractEffect
{
    public const DAMAGE_PER_STACK = 5;

    public function key(): string
    {
        return 'absorbed_souls';
    }

    public function name(): string
    {
        return 'Absorbed Souls';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Spell damage +' . self::DAMAGE_PER_STACK * $instance->stacks . '.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Stack;
    }

    public function dispellable(): bool
    {
        return false;
    }

    /**
     * Extra spell damage for the combatant's current stacks.
     */
    public static function bonusDamage(CombatantState $combatant, Battle $battle): int
    {
        return $battle->effects->stacks($combatant, 'absorbed_souls') * self::DAMAGE_PER_STACK;
    }
}
