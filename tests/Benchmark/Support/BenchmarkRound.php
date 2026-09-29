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
        public readonly int $specialSpellCount = 0,
        public readonly int $protectionCount = 0,
    ) {
    }

    public function dominionCount(): int
    {
        return count($this->dominionIds);
    }

    /**
     * Dominions carrying a spell that makes performSpellEffects call
     * Dominion::update(), which fires DominionSaved and so pays for a second
     * precalculateTick. Seeded onto the front of the list.
     *
     * @return array<int, int>
     */
    public function affectedDominionIds(): array
    {
        return array_slice($this->dominionIds, 0, $this->specialSpellCount);
    }

    /**
     * The rest - the common case, which should precalculate exactly once.
     *
     * @return array<int, int>
     */
    public function unaffectedDominionIds(): array
    {
        return array_slice($this->dominionIds, $this->specialSpellCount);
    }

    public function describe(): string
    {
        return sprintf(
            '%d dominions across %d realms (%d with users, %d bots), %d queue rows, %d spell rows, %d with special spells, %d in protection',
            $this->dominionCount(),
            $this->realmCount,
            $this->playerCount,
            $this->dominionCount() - $this->playerCount,
            $this->queueRowCount,
            $this->spellRowCount,
            $this->specialSpellCount,
            $this->protectionCount
        );
    }
}
