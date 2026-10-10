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
 * Each stack removes a third of the starting maximum health. Once turned, defense drops by 5.
 *
 * Instance data: full_health (int), turned (bool).
 */
class Moonstruck extends AbstractEffect
{
    public const TURNED_DEFENSE_LOSS = 5;

    public function key(): string
    {
        return 'moonstruck';
    }

    public function name(): string
    {
        return 'Moonstruck';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Maximum health reduced by ' . $this->loss($instance) . '. Only Cleanse can stop the curse taking hold.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Stack;
    }

    public function dispellable(): bool
    {
        return false;
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        $modifiers = [Modifier::flat(Stat::Health, -$this->loss($instance))];
        if (!empty($instance->data['turned'])) {
            $modifiers[] = Modifier::flat(Stat::Defense, -self::TURNED_DEFENSE_LOSS);
        }

        return $modifiers;
    }

    private function loss(EffectInstance $instance): int
    {
        return (int) ceil(($instance->data['full_health'] ?? 0) / 3) * $instance->stacks;
    }
}
