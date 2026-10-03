<?php

namespace OpenDominion\HeroCombat\Engine\Events;

/**
 * A structured record of something that happened. Stored alongside the rendered text so
 * the UI and tests do not have to parse descriptions.
 */
final readonly class BattleEvent
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(public EventType $type, public array $payload = [])
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['type' => $this->type->value] + $this->payload;
    }
}
