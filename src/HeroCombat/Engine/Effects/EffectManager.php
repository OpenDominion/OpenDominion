<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

use OpenDominion\HeroCombat\Contracts\Effect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Events\BattleEvent;
use OpenDominion\HeroCombat\Engine\Events\EventType;

/**
 * Applies, finds, ticks and removes effect instances across all three scopes.
 */
final class EffectManager
{
    public function __construct(private Battle $battle)
    {
    }

    /**
     * Applies an effect to a combatant.
     *
     * @param array<string, mixed> $data
     */
    public function apply(
        CombatantState $target,
        string $key,
        ?int $turns = null,
        int $stacks = 1,
        array $data = [],
        ?CombatantState $source = null,
    ): ?EffectInstance {
        $effect = $this->battle->registry->effect($key);

        if ($this->isImmune($target, $effect)) {
            return null;
        }

        return $this->place(
            $target->effects,
            $effect,
            $turns,
            $stacks,
            $data,
            $source,
            EffectScope::Combatant,
            $target->id,
            null,
            $target,
        );
    }

    /**
     * Applies an effect to everyone on a team (subject to Effect::appliesTo).
     *
     * @param array<string, mixed> $data
     */
    public function applyToTeam(
        int $team,
        string $key,
        ?int $turns = null,
        int $stacks = 1,
        array $data = [],
        ?CombatantState $source = null,
    ): EffectInstance {
        $effect = $this->battle->registry->effect($key);
        $this->battle->state->teamEffects[$team] ??= [];

        return $this->place(
            $this->battle->state->teamEffects[$team],
            $effect,
            $turns,
            $stacks,
            $data,
            $source,
            EffectScope::Team,
            null,
            $team,
            null,
        );
    }

    /**
     * Applies an effect to the battlefield (subject to Effect::appliesTo).
     *
     * @param array<string, mixed> $data
     */
    public function applyToField(
        string $key,
        ?int $turns = null,
        int $stacks = 1,
        array $data = [],
        ?CombatantState $source = null,
    ): EffectInstance {
        $effect = $this->battle->registry->effect($key);

        return $this->place(
            $this->battle->state->fieldEffects,
            $effect,
            $turns,
            $stacks,
            $data,
            $source,
            EffectScope::Field,
            null,
            null,
            null,
        );
    }

    public function find(CombatantState $combatant, string $key): ?EffectInstance
    {
        foreach ($combatant->effects as $instance) {
            if ($instance->key === $key) {
                return $instance;
            }
        }

        return null;
    }

    public function has(CombatantState $combatant, string $key): bool
    {
        return $this->find($combatant, $key) !== null;
    }

    public function stacks(CombatantState $combatant, string $key): int
    {
        return $this->find($combatant, $key)?->stacks ?? 0;
    }

    /**
     * Whether any effect applying to the combatant (from any scope) grants the tag.
     */
    public function hasTag(CombatantState $combatant, CombatTag $tag): bool
    {
        foreach ($this->applicableTo($combatant) as [$effect]) {
            if (in_array($tag, $effect->tags(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * All effects that apply to a combatant: its own, its team's and the field's.
     *
     * @return array<int, array{0: Effect, 1: EffectInstance}>
     */
    public function applicableTo(CombatantState $combatant): array
    {
        $pairs = [];

        foreach ($combatant->effects as $instance) {
            $pairs[] = [$this->battle->registry->effect($instance->key), $instance];
        }

        $shared = array_merge(
            $this->battle->state->teamEffects[$combatant->team] ?? [],
            $this->battle->state->fieldEffects,
        );

        foreach ($shared as $instance) {
            $effect = $this->battle->registry->effect($instance->key);
            if ($effect->appliesTo($instance, $combatant, $this->battle)) {
                $pairs[] = [$effect, $instance];
            }
        }

        return $pairs;
    }

    /**
     * Every instance in the battle, in every scope.
     *
     * @return EffectInstance[]
     */
    public function all(): array
    {
        $instances = [];

        foreach ($this->battle->state->combatants as $combatant) {
            array_push($instances, ...$combatant->effects);
        }
        foreach ($this->battle->state->teamEffects as $teamInstances) {
            array_push($instances, ...$teamInstances);
        }
        array_push($instances, ...$this->battle->state->fieldEffects);

        return $instances;
    }

    public function remove(EffectInstance $instance, bool $expired = false): void
    {
        if (!$this->detach($instance)) {
            return;
        }

        $effect = $this->battle->registry->effect($instance->key);

        if ($expired) {
            $effect->onExpire($instance, $this->battle);
        }
        $effect->onRemove($instance, $this->battle);

        $this->battle->event(new BattleEvent(EventType::EffectRemoved, [
            'effect' => $instance->key,
            'owner' => $instance->ownerId,
            'team' => $instance->ownerTeam,
            'expired' => $expired,
        ]));
    }

    public function removeByKey(CombatantState $combatant, string $key): int
    {
        $removed = 0;
        foreach ($combatant->effects as $instance) {
            if ($instance->key === $key) {
                $this->remove($instance);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Removes the combatant's own effects carrying the tag.
     */
    public function removeByTag(CombatantState $combatant, CombatTag $tag, bool $onlyDispellable = true): int
    {
        $removed = 0;
        foreach ($combatant->effects as $instance) {
            $effect = $this->battle->registry->effect($instance->key);
            if (!in_array($tag, $effect->tags(), true)) {
                continue;
            }
            if ($onlyDispellable && !$effect->dispellable()) {
                continue;
            }
            $this->remove($instance);
            $removed++;
        }

        return $removed;
    }

    /**
     * Removes every instance (any scope) that the given combatant applied.
     */
    public function removeBySource(int $sourceId, ?string $key = null): int
    {
        $removed = 0;
        foreach ($this->all() as $instance) {
            if ($instance->sourceId === $sourceId && ($key === null || $instance->key === $key)) {
                $this->remove($instance);
                $removed++;
            }
        }

        return $removed;
    }

    public function consume(EffectInstance $instance, int $stacks = 1): void
    {
        $instance->stacks -= $stacks;
        if ($instance->stacks <= 0) {
            $this->remove($instance);
        }
    }

    /**
     * Counts down TurnEnd and OnDamageTaken durations. Instances applied while ticking are
     * left alone so an effect applied at end of turn lasts through the next turn.
     */
    public function tickTurnEnd(): void
    {
        foreach ($this->all() as $instance) {
            $effect = $this->battle->registry->effect($instance->key);
            if (!in_array($effect->expiry(), [ExpiryTiming::TurnEnd, ExpiryTiming::OnDamageTaken], true)) {
                continue;
            }
            $this->countDown($instance);
        }
    }

    public function tickOwnerActionEnd(CombatantState $owner): void
    {
        foreach ($owner->effects as $instance) {
            if ($this->battle->registry->effect($instance->key)->expiry() === ExpiryTiming::OwnerActionEnd) {
                $this->countDown($instance);
            }
        }
    }

    public function notifyDamageTaken(CombatantState $owner): void
    {
        foreach ($owner->effects as $instance) {
            if ($this->battle->registry->effect($instance->key)->expiry() === ExpiryTiming::OnDamageTaken) {
                $this->remove($instance, true);
            }
        }
    }

    private function countDown(EffectInstance $instance): void
    {
        if ($instance->remainingTurns === null) {
            return;
        }

        $instance->remainingTurns--;
        if ($instance->remainingTurns <= 0) {
            $this->remove($instance, true);
        }
    }

    private function isImmune(CombatantState $target, Effect $incoming): bool
    {
        $incomingTags = $incoming->tags();
        if ($incomingTags === []) {
            return false;
        }

        foreach ($this->applicableTo($target) as [$effect]) {
            if (array_intersect(
                array_map(fn (CombatTag $tag) => $tag->value, $effect->immuneToTags()),
                array_map(fn (CombatTag $tag) => $tag->value, $incomingTags),
            ) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param EffectInstance[] $list
     * @param array<string, mixed> $data
     */
    private function place(
        array &$list,
        Effect $effect,
        ?int $turns,
        int $stacks,
        array $data,
        ?CombatantState $source,
        EffectScope $scope,
        ?int $ownerId,
        ?int $ownerTeam,
        ?CombatantState $owner,
    ): EffectInstance {
        $turns ??= $effect->defaultDuration();
        $existing = null;
        foreach ($list as $instance) {
            if ($instance->key === $effect->key()) {
                $existing = $instance;
                break;
            }
        }

        if ($existing !== null) {
            switch ($effect->stacking()) {
                case StackingRule::Refresh:
                    $existing->remainingTurns = $turns;
                    $existing->data = array_merge($existing->data, $data);
                    $this->logApplied($existing);
                    return $existing;

                case StackingRule::Stack:
                    $max = $effect->maxStacks($this->battle, $owner);
                    $existing->stacks = $max === null ? $existing->stacks + $stacks : min($max, $existing->stacks + $stacks);
                    $existing->remainingTurns = $turns;
                    $existing->data = array_merge($existing->data, $data);
                    $this->logApplied($existing);
                    return $existing;

                case StackingRule::Replace:
                    $this->remove($existing);
                    break;

                case StackingRule::Independent:
                    break;
            }
        }

        $instance = new EffectInstance(
            key: $effect->key(),
            sourceId: $source?->id,
            remainingTurns: $turns,
            stacks: $stacks,
            data: $data,
            appliedTurn: $this->battle->state->turn,
            sequence: $this->battle->state->nextEffectSequence(),
        );
        $instance->scope = $scope;
        $instance->ownerId = $ownerId;
        $instance->ownerTeam = $ownerTeam;
        $list[] = $instance;

        $effect->onApply($instance, $this->battle);
        $this->logApplied($instance);

        return $instance;
    }

    private function logApplied(EffectInstance $instance): void
    {
        $this->battle->event(new BattleEvent(EventType::EffectApplied, [
            'effect' => $instance->key,
            'owner' => $instance->ownerId,
            'team' => $instance->ownerTeam,
            'source' => $instance->sourceId,
            'turns' => $instance->remainingTurns,
            'stacks' => $instance->stacks,
        ]));
    }

    private function detach(EffectInstance $target): bool
    {
        $state = $this->battle->state;

        if ($target->scope === EffectScope::Combatant) {
            $owner = $state->combatants[$target->ownerId] ?? null;
            if ($owner === null) {
                return false;
            }
            return $this->removeFromList($owner->effects, $target);
        }

        if ($target->scope === EffectScope::Team) {
            if (!isset($state->teamEffects[$target->ownerTeam])) {
                return false;
            }
            return $this->removeFromList($state->teamEffects[$target->ownerTeam], $target);
        }

        return $this->removeFromList($state->fieldEffects, $target);
    }

    /**
     * @param EffectInstance[] $list
     */
    private function removeFromList(array &$list, EffectInstance $target): bool
    {
        foreach ($list as $index => $instance) {
            if ($instance === $target) {
                array_splice($list, $index, 1);
                return true;
            }
        }

        return false;
    }
}
