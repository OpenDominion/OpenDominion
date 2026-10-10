<?php

namespace OpenDominion\HeroCombat\Content;

use OpenDominion\HeroCombat\Contracts\Ability;
use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Targeting\TargetRule;

/**
 * Sensible defaults so most abilities only implement key, name, description and resolve.
 */
abstract class AbstractAbility implements Ability
{
    public function targetRule(): TargetRule
    {
        return TargetRule::SingleEnemy;
    }

    public function priority(): int
    {
        return 0;
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 0;
    }

    public function maxCharges(CombatantState $actor, Battle $battle): ?int
    {
        return null;
    }

    public function tags(): array
    {
        return [];
    }

    public function blockedByTags(): array
    {
        return [CombatTag::Stunned];
    }

    public function requiredTags(): array
    {
        return [];
    }

    public function canUse(CombatantState $actor, Battle $battle): bool
    {
        return true;
    }

    public function selectable(): bool
    {
        return true;
    }

    public function onDeclare(ActionContext $context): void
    {
    }

    public function messages(): array
    {
        return [];
    }
}
