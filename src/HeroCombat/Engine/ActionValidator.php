<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Contracts\Ability;

/**
 * Cooldown, charge, tag and ability-specific checks, shared by turn resolution, AI and
 * the queueing UI.
 */
final class ActionValidator
{
    public const PASS = 'pass';

    public function __construct(private Battle $battle)
    {
    }

    /**
     * Whether the actor can use the ability on the current turn.
     */
    public function canPerform(CombatantState $actor, string $abilityKey): bool
    {
        if ($abilityKey === self::PASS) {
            return true;
        }

        if (!$actor->hasAbility($abilityKey) || !$this->battle->registry->hasAbility($abilityKey)) {
            return false;
        }

        $ability = $this->battle->registry->ability($abilityKey);

        return $this->isOffCooldown($actor, $ability, $this->battle->turn(), $actor->cooldowns)
            && $this->hasCharges($actor, $ability, $actor->charges)
            && $this->tagsAllow($actor, $ability)
            && $ability->canUse($actor, $this->battle);
    }

    /**
     * Whether the ability can be appended to the actor's queue, projecting cooldowns and
     * charges forward through the actions already queued.
     */
    public function canQueue(CombatantState $actor, string $abilityKey): bool
    {
        if (count($actor->queue) === 0) {
            return $this->canPerform($actor, $abilityKey);
        }

        if (!$actor->hasAbility($abilityKey) || !$this->battle->registry->hasAbility($abilityKey)) {
            return false;
        }

        $cooldowns = $actor->cooldowns;
        $charges = $actor->charges;
        $turn = $this->battle->turn();

        foreach ($actor->queue as $queued) {
            if ($this->battle->registry->hasAbility($queued['ability'])) {
                $this->project($actor, $this->battle->registry->ability($queued['ability']), $turn, $cooldowns, $charges);
            }
            $turn++;
        }

        $ability = $this->battle->registry->ability($abilityKey);

        return $this->isOffCooldown($actor, $ability, $turn, $cooldowns)
            && $this->hasCharges($actor, $ability, $charges);
    }

    /**
     * @return Ability[]
     */
    public function usableAbilities(CombatantState $actor): array
    {
        $usable = [];
        foreach ($actor->abilities as $key) {
            if ($this->canPerform($actor, $key)) {
                $usable[$key] = $this->battle->registry->ability($key);
            }
        }

        return $usable;
    }

    /**
     * Starts the cooldown and spends a charge after an ability resolves.
     */
    public function recordUse(CombatantState $actor, Ability $ability): void
    {
        $this->project($actor, $ability, $this->battle->turn(), $actor->cooldowns, $actor->charges);
    }

    public function turnsUntilReady(CombatantState $actor, string $abilityKey): int
    {
        return max(0, ($actor->cooldowns[$abilityKey] ?? 0) - $this->battle->turn());
    }

    public function chargesRemaining(CombatantState $actor, string $abilityKey): ?int
    {
        $max = $this->battle->registry->ability($abilityKey)->maxCharges($actor, $this->battle);

        return $max === null ? null : ($actor->charges[$abilityKey] ?? $max);
    }

    /**
     * @param array<string, int> $cooldowns
     * @param array<string, int> $charges
     */
    private function project(CombatantState $actor, Ability $ability, int $turn, array &$cooldowns, array &$charges): void
    {
        $cooldown = $ability->cooldown($actor, $this->battle);
        if ($cooldown > 0) {
            $cooldowns[$ability->key()] = $turn + $cooldown + 1;
        }

        $max = $ability->maxCharges($actor, $this->battle);
        if ($max !== null) {
            $charges[$ability->key()] = max(0, ($charges[$ability->key()] ?? $max) - 1);
        }
    }

    /**
     * @param array<string, int> $cooldowns
     */
    private function isOffCooldown(CombatantState $actor, Ability $ability, int $turn, array $cooldowns): bool
    {
        return ($cooldowns[$ability->key()] ?? 0) <= $turn;
    }

    /**
     * @param array<string, int> $charges
     */
    private function hasCharges(CombatantState $actor, Ability $ability, array $charges): bool
    {
        $max = $ability->maxCharges($actor, $this->battle);

        return $max === null || ($charges[$ability->key()] ?? $max) > 0;
    }

    private function tagsAllow(CombatantState $actor, Ability $ability): bool
    {
        foreach ($ability->blockedByTags() as $tag) {
            if ($this->battle->hasTag($actor, $tag)) {
                return false;
            }
        }

        foreach ($ability->requiredTags() as $tag) {
            if (!$this->battle->hasTag($actor, $tag)) {
                return false;
            }
        }

        return true;
    }
}
