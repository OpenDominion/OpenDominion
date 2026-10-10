<?php

namespace OpenDominion\HeroCombat\Engine\Events;

/**
 * One row of the combat log: an action resolving, or a status line.
 */
final class LogEntry
{
    public const STATUS = 'status';

    /** @var string[] */
    public array $lines = [];

    /** @var BattleEvent[] */
    public array $events = [];

    /** Damage dealt by the actor this entry. */
    public int $damage = 0;

    /** Net health change of the actor this entry. */
    public int $health = 0;

    public function __construct(
        public int $turn,
        public ?int $actorId,
        public string $action,
        public ?int $targetId = null,
    ) {
    }

    public function description(): string
    {
        return implode(' ', array_filter($this->lines, fn ($line) => $line !== ''));
    }
}
