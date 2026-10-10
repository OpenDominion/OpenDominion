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
 * Battle-long stat gains absorbed from a fallen ally. Data: bonuses (stat => amount).
 */
class Empowered extends AbstractEffect
{
    public function key(): string
    {
        return 'empowered';
    }

    public function name(): string
    {
        return 'Empowered';
    }

    public function description(EffectInstance $instance): string
    {
        $parts = [];
        foreach ($instance->data['bonuses'] ?? [] as $stat => $amount) {
            $parts[] = ucfirst($stat) . " +{$amount}";
        }

        return 'Strengthened by a fallen ally: ' . implode(', ', $parts) . '.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
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
        foreach ($instance->data['bonuses'] ?? [] as $stat => $amount) {
            $modifiers[] = Modifier::flat(Stat::from($stat), $amount);
        }

        return $modifiers;
    }
}
