<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * A stacking, battle-long stat change (replaces permanently mutating base stats).
 */
abstract class AbstractStatShift extends AbstractEffect
{
    abstract protected function stat(): Stat;

    abstract protected function valuePerStack(): int;

    public function kind(): EffectKind
    {
        return $this->valuePerStack() >= 0 ? EffectKind::Buff : EffectKind::Debuff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Stack;
    }

    public function description(EffectInstance $instance): string
    {
        $total = $this->valuePerStack() * $instance->stacks;
        $sign = $total >= 0 ? '+' : '';

        return "{$this->stat()->label()} {$sign}{$total}.";
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [Modifier::flat($this->stat(), $this->valuePerStack() * $instance->stacks)];
    }
}
