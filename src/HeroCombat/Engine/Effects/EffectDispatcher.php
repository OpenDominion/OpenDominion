<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

use OpenDominion\HeroCombat\Contracts\Effect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * Runs effect hooks in a defined order: handler priority (high first), then the order
 * in which the instances were applied.
 */
final class EffectDispatcher
{
    public function __construct(private Battle $battle)
    {
    }

    /**
     * Invokes the hook on every effect applying to the given subjects.
     */
    public function run(Hook $hook, array $subjects, mixed ...$arguments): void
    {
        foreach ($this->handlers($hook, $subjects) as [$effect, $instance]) {
            $effect->{$hook->value}($instance, ...$arguments);
        }
    }

    /**
     * Invokes the hook until one handler returns true.
     */
    public function runUntilTrue(Hook $hook, array $subjects, mixed ...$arguments): bool
    {
        foreach ($this->handlers($hook, $subjects) as [$effect, $instance]) {
            if ($effect->{$hook->value}($instance, ...$arguments) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Invokes the hook until one handler returns a non-null value, and returns it.
     */
    public function first(Hook $hook, array $subjects, mixed ...$arguments): mixed
    {
        foreach ($this->handlers($hook, $subjects) as [$effect, $instance]) {
            $result = $effect->{$hook->value}($instance, ...$arguments);
            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Invokes the hook once on every instance in the battle, regardless of who it applies to.
     */
    public function runEverywhere(Hook $hook, mixed ...$arguments): void
    {
        $pairs = array_map(
            fn (EffectInstance $instance) => [$this->battle->registry->effect($instance->key), $instance],
            $this->battle->effects->all(),
        );

        foreach ($this->sort($hook, $pairs) as [$effect, $instance]) {
            if ($this->stillAttached($instance)) {
                $effect->{$hook->value}($instance, ...$arguments);
            }
        }
    }

    /**
     * Invokes the hook only on a combatant's own instances (not team or field instances).
     */
    public function runOwn(Hook $hook, CombatantState $owner, mixed ...$arguments): void
    {
        $pairs = array_map(
            fn (EffectInstance $instance) => [$this->battle->registry->effect($instance->key), $instance],
            $owner->effects,
        );

        foreach ($this->sort($hook, $pairs) as [$effect, $instance]) {
            $effect->{$hook->value}($instance, ...$arguments);
        }
    }

    /**
     * @param CombatantState[] $subjects
     * @return array<int, array{0: Effect, 1: EffectInstance}>
     */
    public function handlers(Hook $hook, array $subjects): array
    {
        $pairs = [];
        $seen = [];

        foreach ($subjects as $subject) {
            foreach ($this->battle->effects->applicableTo($subject) as $pair) {
                $id = spl_object_id($pair[1]);
                if (!isset($seen[$id])) {
                    $seen[$id] = true;
                    $pairs[] = $pair;
                }
            }
        }

        return $this->sort($hook, $pairs);
    }

    /**
     * @param array<int, array{0: Effect, 1: EffectInstance}> $pairs
     * @return array<int, array{0: Effect, 1: EffectInstance}>
     */
    private function sort(Hook $hook, array $pairs): array
    {
        usort($pairs, function (array $a, array $b) use ($hook) {
            return [$b[0]->handlerPriority($hook), $a[1]->sequence] <=> [$a[0]->handlerPriority($hook), $b[1]->sequence];
        });

        return $pairs;
    }

    private function stillAttached(EffectInstance $target): bool
    {
        foreach ($this->battle->effects->all() as $instance) {
            if ($instance === $target) {
                return true;
            }
        }

        return false;
    }
}
