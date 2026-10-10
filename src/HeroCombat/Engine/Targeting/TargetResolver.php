<?php

namespace OpenDominion\HeroCombat\Engine\Targeting;

use OpenDominion\HeroCombat\Contracts\Ability;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;

/**
 * Turns an ability's target rule and a requested target into the combatants it affects.
 */
final class TargetResolver
{
    public function __construct(private Battle $battle)
    {
    }

    /**
     * @return CombatantState[]
     */
    public function resolve(CombatantState $actor, Ability $ability, ?int $requestedId): array
    {
        $requested = $this->battle->combatant($requestedId);

        return match ($ability->targetRule()) {
            TargetRule::Self => [$actor],
            TargetRule::SingleEnemy => $this->singleEnemy($actor, $requested),
            TargetRule::RandomEnemy => $this->randomEnemy($actor),
            TargetRule::SingleAlly => $requested !== null && $requested->isAlive() && $requested->team === $actor->team ? [$requested] : [],
            TargetRule::SingleAny => $requested !== null && $requested->isAlive() ? [$requested] : [],
            TargetRule::AllEnemies => $this->battle->enemiesOf($actor),
            TargetRule::AllAllies => $this->battle->alliesOf($actor),
            TargetRule::AllOthers => array_values(array_filter($this->battle->living(), fn ($c) => $c->id !== $actor->id)),
        };
    }

    /**
     * Whether a player may queue this target for this ability.
     */
    public function isValidChoice(CombatantState $actor, Ability $ability, ?CombatantState $target): bool
    {
        $rule = $ability->targetRule();

        if (!$rule->needsChosenTarget()) {
            return true;
        }

        if ($target === null || !$target->isAlive()) {
            return false;
        }

        return match ($rule) {
            TargetRule::SingleEnemy => $target->team !== $actor->team,
            TargetRule::SingleAlly => $target->team === $actor->team,
            default => true,
        };
    }

    /**
     * @return CombatantState[]
     */
    private function singleEnemy(CombatantState $actor, ?CombatantState $requested): array
    {
        $provoker = $this->provoker($actor);
        if ($provoker !== null) {
            return [$provoker];
        }

        if ($requested !== null && $requested->isAlive() && $requested->team !== $actor->team) {
            return [$requested];
        }

        return $this->randomEnemy($actor);
    }

    /**
     * @return CombatantState[]
     */
    private function randomEnemy(CombatantState $actor): array
    {
        $enemies = $this->battle->enemiesOf($actor);

        return $enemies === [] ? [] : [$this->battle->random->pick($enemies)];
    }

    private function provoker(CombatantState $actor): ?CombatantState
    {
        if (!$this->battle->hasTag($actor, CombatTag::Provoked)) {
            return null;
        }

        foreach ($actor->effects as $instance) {
            if ($instance->key !== 'provoked') {
                continue;
            }
            $provoker = $this->battle->combatant($instance->sourceId);
            if ($provoker !== null && $provoker->isAlive() && $provoker->team !== $actor->team) {
                return $provoker;
            }
        }

        return null;
    }
}
