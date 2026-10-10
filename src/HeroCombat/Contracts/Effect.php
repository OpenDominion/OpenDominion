<?php

namespace OpenDominion\HeroCombat\Contracts;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\ExpiryTiming;
use OpenDominion\HeroCombat\Engine\Effects\Hook;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;
use OpenDominion\HeroCombat\Engine\Intent;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;

/**
 * A buff, debuff, status or innate passive. Definitions are stateless; per-application
 * state lives on the EffectInstance passed to every method.
 */
interface Effect
{
    public function key(): string;

    public function name(): string;

    public function description(EffectInstance $instance): string;

    public function kind(): EffectKind;

    public function stacking(): StackingRule;

    public function maxStacks(Battle $battle, ?CombatantState $owner): ?int;

    /** Default duration in turns, or null for the rest of the battle. */
    public function defaultDuration(): ?int;

    public function expiry(): ExpiryTiming;

    /** @return CombatTag[] tags granted to whoever this effect applies to */
    public function tags(): array;

    /** @return CombatTag[] effects carrying any of these tags cannot be applied to the holder */
    public function immuneToTags(): array;

    public function dispellable(): bool;

    public function visible(): bool;

    /** Ordering among handlers reacting to the same hook; higher runs first. */
    public function handlerPriority(Hook $hook): int;

    /** For team and field scoped instances: does this instance apply to the subject? */
    public function appliesTo(EffectInstance $instance, CombatantState $subject, Battle $battle): bool;

    /** @return Modifier[] */
    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array;

    public function onApply(EffectInstance $instance, Battle $battle): void;

    /** Natural expiry only. */
    public function onExpire(EffectInstance $instance, Battle $battle): void;

    /** Any removal: expiry, dispel, consumption or replacement. */
    public function onRemove(EffectInstance $instance, Battle $battle): void;

    public function onTurnStart(EffectInstance $instance, Battle $battle): void;

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void;

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent;

    public function beforeDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    public function redirectDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    public function onEvaded(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    public function beforeDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    /** Return true to prevent the hit from being lethal. */
    public function onLethalDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): bool;

    public function afterDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    public function afterDamageDealt(EffectInstance $instance, DamageContext $damage, Battle $battle): void;

    public function onAbilityUsed(EffectInstance $instance, ActionContext $context, Battle $battle): void;

    /** Called on the dying combatant's own effects. */
    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void;

    /** Called on every other effect in the battle when anyone dies. */
    public function onAnyDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void;
}
