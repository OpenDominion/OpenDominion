<?php

namespace OpenDominion\HeroCombat\Engine;

/**
 * Advances a battle for as long as every living combatant is ready.
 */
final class BattleEngine
{
    public const MAX_TURNS_PER_ADVANCE = 100;

    public function __construct(private TurnResolver $resolver = new TurnResolver())
    {
    }

    public function isReady(Battle $battle): bool
    {
        foreach ($battle->living() as $combatant) {
            if (!$combatant->isReady()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolves a single turn, regardless of readiness.
     */
    public function resolveTurn(Battle $battle): void
    {
        if (!$battle->state->finished) {
            $this->resolver->resolve($battle);
        }
    }

    /**
     * Resolves turns until the battle ends or someone needs to choose an action.
     *
     * @param callable(Battle): void|null $beforeTurn hook to e.g. reseed randomness per turn
     * @return int number of turns resolved
     */
    public function advance(Battle $battle, ?callable $beforeTurn = null): int
    {
        $turns = 0;

        while (!$battle->state->finished && $this->isReady($battle) && $turns < self::MAX_TURNS_PER_ADVANCE) {
            if ($beforeTurn !== null) {
                $beforeTurn($battle);
            }
            $this->resolver->resolve($battle);
            $turns++;
        }

        return $turns;
    }
}
