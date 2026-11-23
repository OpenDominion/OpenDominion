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
     * @param array<int, array{name: string, stats: array<string, int>}> $party every hero on the players' side, leader first
     */
    public function __construct(
        public int $priorWins = 0,
        public array $leaderStats = [],
        public array $party = [],
    ) {
    }
}
