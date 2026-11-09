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
    public const SOURCE_ALTERED = 'altered';

    /**
     * @param Intent|null $replaces the intent an effect replaced (see Hook::AlterIntent)
     */
    public function __construct(
        public int $actorId,
        public string $abilityKey,
        public ?int $targetId = null,
        public string $source = self::SOURCE_AI,
        public ?Intent $replaces = null,
    ) {
    }

    public function withActor(int $actorId): self
    {
        return new self($actorId, $this->abilityKey, $this->targetId, $this->source, $this->replaces);
    }

    /**
     * This intent marked as an effect's replacement for the original.
     */
    public function replacing(Intent $original): self
    {
        return new self($original->actorId, $this->abilityKey, $this->targetId, self::SOURCE_ALTERED, $original);
    }
}
