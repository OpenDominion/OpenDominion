<?php

namespace OpenDominion\Tests\Benchmark\Support;

use OpenDominion\Models\Round;

/**
 * The fixture produced by BenchmarkRoundSeeder.
 */
final class BenchmarkRound
{
    /**
     * @param array<int, int> $dominionIds
     */
    public function __construct(
        public readonly Round $round,
        public readonly array $dominionIds,
        public readonly int $realmCount,
        public readonly int $playerCount,
        public readonly int $queueRowCount,
        public readonly int $spellRowCount,
    ) {
    }

    public function dominionCount(): int
    {
        return count($this->dominionIds);
    }

    public function describe(): string
    {
        return sprintf(
            '%d dominions across %d realms (%d with users, %d bots), %d queue rows, %d spell rows',
            $this->dominionCount(),
            $this->realmCount,
            $this->playerCount,
            $this->dominionCount() - $this->playerCount,
            $this->queueRowCount,
            $this->spellRowCount
        );
    }
}
