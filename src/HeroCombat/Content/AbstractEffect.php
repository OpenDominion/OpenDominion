<?php

namespace OpenDominion\HeroCombat\Content;

use OpenDominion\HeroCombat\Contracts\Effect;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\ExpiryTiming;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * No-op defaults for every hook. Concrete effects override only what they react to.
 */
abstract class AbstractEffect implements Effect
{
    public function description(EffectInstance $instance): string
    {
        return $this->name();
    }

    public function kind(): EffectKind
    {
        return EffectKind::Status;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Refresh;
    }

    public function maxStacks(Battle $battle, ?CombatantState $owner): ?int
    {
        return null;
    }

    public function defaultDuration(): ?int
    {
        return null;
    }

    public function expiry(): ExpiryTiming
    {
        return ExpiryTiming::TurnEnd;
    }

    public function tags(): array
    {
        return [];
    }

    public function immuneToTags(): array
    {
        return [];
    }

    public function dispellable(): bool
    {
        return $this->kind() !== EffectKind::Innate;
    }

    public function visible(): bool
    {
        return true;
    }

    public function handlerPriority(Hook $hook): int
    {
        return 0;
    }

    public function appliesTo(EffectInstance $instance, CombatantState $subject, Battle $battle): bool
    {
        return !in_array($subject->id, (array) ($instance->data['exclude'] ?? []), true);
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [];
    }

    public function onApply(EffectInstance $instance, Battle $battle): void
    {
    }

    public function onExpire(EffectInstance $instance, Battle $battle): void
    {
    }

    public function onRemove(EffectInstance $instance, Battle $battle): void
    {
    }

    public function onTurnStart(EffectInstance $instance, Battle $battle): void
    {
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        return null;
    }

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function redirectDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function onEvaded(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function beforeDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function onLethalDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): bool
    {
        return false;
    }

    public function afterDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function afterDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
    }

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void
    {
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
    }

    public function onAnyDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
    }

    /**
     * The combatant holding a combatant-scoped instance.
     */
    protected function owner(EffectInstance $instance, Battle $battle): ?CombatantState
    {
        return $battle->combatant($instance->ownerId);
    }
}
