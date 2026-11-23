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

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 0;
    }

    public function maxCharges(CombatantState $actor, Battle $battle): ?int
    {
        return null;
    }

    public function canTarget(CombatantState $actor, CombatantState $target, Battle $battle): bool
    {
        return true;
    }

    public function recordedAction(ActionContext $context): string
    {
        return $this->key();
    }

    public function warning(CombatantState $actor): ?string
    {
        return null;
    }

    public function rulesNotes(): array
    {
        return [];
    }

    /**
     * The living ally chosen for this action, if the actor chose someone other than themselves.
     */
    protected function chosenAlly(ActionContext $context): ?CombatantState
    {
        $ally = $context->battle->combatant($context->intent->targetId);

        if ($ally === null || $ally->id === $context->actor->id || !$ally->isAlive() || $ally->team !== $context->actor->team) {
            return null;
        }

        return $ally;
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

    public function onePerTeam(): bool
    {
        return false;
    }

    public function onDeclare(ActionContext $context): void
    {
    }

    public function messages(): array
    {
        return [];
    }
}
