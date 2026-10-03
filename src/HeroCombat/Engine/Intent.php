<?php

namespace OpenDominion\HeroCombat\Engine;

/**
 * What a combatant has chosen to do this turn.
 */
final readonly class Intent
{
    public const SOURCE_QUEUE = 'queue';
    public const SOURCE_AI = 'ai';
    public const SOURCE_FORCED = 'forced';

    public function __construct(
        public int $actorId,
        public string $abilityKey,
        public ?int $targetId = null,
        public string $source = self::SOURCE_AI,
    ) {
    }

    public function withActor(int $actorId): self
    {
        return new self($actorId, $this->abilityKey, $this->targetId, $this->source);
    }
}
