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

    /**
     * Rule clarifications shown in a tooltip beside the short description. Empty until the
     * ability's balance has been reviewed.
     *
     * @return string[]
     */
    public function rulesNotes(): array;

    public function targetRule(): TargetRule;

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

    /** What this action counts as next turn; Defend on an ally counts as a Guard. */
    public function recordedAction(ActionContext $context): string;

    /** A short warning to show on the action button, such as a penalty for using it now. */
    public function warning(CombatantState $actor): ?string;

    /** Whether this ability may be used on this particular target. */
    public function canTarget(CombatantState $actor, CombatantState $target, Battle $battle): bool;

    /** Whether players can pick this ability from their action menu. */
    public function selectable(): bool;

    /** Whether only one combatant per team may choose this ability for the same turn. */
    public function onePerTeam(): bool;

    /** Runs for every intent before any intent resolves; stances are applied here. */
    public function onDeclare(ActionContext $context): void;

    public function resolve(ActionContext $context): void;

    /** @return array<string, string> named message templates using {placeholders} */
    public function messages(): array;
}
