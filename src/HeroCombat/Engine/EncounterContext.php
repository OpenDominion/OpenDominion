<?php

namespace OpenDominion\HeroCombat\Engine;

/**
 * Context an encounter may use to build and scale its roster.
 */
final readonly class EncounterContext
{
    /**
     * @param int $priorWins realm victories against this raid tactic ("realm wounds")
     * @param array<string, int> $leaderStats combat stats of the first player, keyed by Stat value
     */
    public function __construct(
        public int $priorWins = 0,
        public array $leaderStats = [],
    ) {
    }
}
