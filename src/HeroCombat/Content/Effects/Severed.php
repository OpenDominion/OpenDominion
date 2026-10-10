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
 * Battle-long stat reductions from a destroyed power source. Data: reductions (stat => amount).
 */
class Severed extends AbstractEffect
{
    public function key(): string
    {
        return 'severed';
    }

    public function name(): string
    {
        return 'Severed';
    }

    public function description(EffectInstance $instance): string
    {
        $parts = [];
        foreach ($instance->data['reductions'] ?? [] as $stat => $amount) {
            $parts[] = ucfirst($stat) . " -{$amount}";
        }

        return 'Cut off from its power source: ' . implode(', ', $parts) . '.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Independent;
    }

    public function dispellable(): bool
    {
        return false;
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        $modifiers = [];
        foreach ($instance->data['reductions'] ?? [] as $stat => $amount) {
            $modifiers[] = Modifier::flat(Stat::from($stat), -$amount);
        }

        return $modifiers;
    }
}
