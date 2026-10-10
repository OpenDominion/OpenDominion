<?php

namespace OpenDominion\HeroCombat\Engine\Effects;

/**
 * One applied effect. Definitions (behavior) live in Effect classes; instances only
 * carry the state that has to survive between turns.
 */
final class EffectInstance
{
    /** Runtime only: where this instance lives. Restored on load. */
    public EffectScope $scope = EffectScope::Combatant;

    /** Runtime only: owning combatant id for combatant-scoped instances. */
    public ?int $ownerId = null;

    /** Runtime only: owning team for team-scoped instances. */
    public ?int $ownerTeam = null;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $key,
        public ?int $sourceId = null,
        public ?int $remainingTurns = null,
        public int $stacks = 1,
        public array $data = [],
        public int $appliedTurn = 0,
        public int $sequence = 0,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'src' => $this->sourceId,
            'turns' => $this->remainingTurns,
            'stacks' => $this->stacks,
            'data' => $this->data,
            'turn' => $this->appliedTurn,
            'seq' => $this->sequence,
        ], fn ($value) => $value !== null && $value !== []);
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            key: $row['key'],
            sourceId: $row['src'] ?? null,
            remainingTurns: $row['turns'] ?? null,
            stacks: $row['stacks'] ?? 1,
            data: $row['data'] ?? [],
            appliedTurn: $row['turn'] ?? 0,
            sequence: $row['seq'] ?? 0,
        );
    }
}
