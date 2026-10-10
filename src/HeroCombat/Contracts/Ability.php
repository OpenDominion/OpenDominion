<?php

namespace OpenDominion\HeroCombat\Contracts;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * An action a combatant can choose on its turn.
 */
interface Ability
{
    public function key(): string;

    public function name(): string;

    public function description(): string;

    public function targetRule(): TargetRule;

    /** Higher priorities resolve earlier within a turn. */
    public function priority(): int;

    /** Turns after use before the ability can be chosen again (0 = no cooldown, 1 = not twice in a row). */
    public function cooldown(CombatantState $actor, Battle $battle): int;

    /** Uses per battle, or null for unlimited. */
    public function maxCharges(CombatantState $actor, Battle $battle): ?int;

    /** @return CombatTag[] */
    public function tags(): array;

    /** @return CombatTag[] the actor cannot use this ability while holding any of these tags */
    public function blockedByTags(): array;

    /** @return CombatTag[] the actor must hold all of these tags */
    public function requiredTags(): array;

    /** Ability-specific usability rules (beyond cooldowns, charges and tags). */
    public function canUse(CombatantState $actor, Battle $battle): bool;

    /** Whether players can pick this ability from their action menu. */
    public function selectable(): bool;

    /** Runs for every intent before any intent resolves; stances are applied here. */
    public function onDeclare(ActionContext $context): void;

    public function resolve(ActionContext $context): void;

    /** @return array<string, string> named message templates using {placeholders} */
    public function messages(): array;
}
