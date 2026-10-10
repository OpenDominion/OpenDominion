<?php

namespace OpenDominion\HeroCombat\Engine\Events;

final class BattleLog
{
    /** @var LogEntry[] */
    private array $entries = [];

    private ?LogEntry $current = null;

    public function begin(int $turn, ?int $actorId, string $action, ?int $targetId = null): LogEntry
    {
        $this->current = new LogEntry($turn, $actorId, $action, $targetId);
        $this->entries[] = $this->current;

        return $this->current;
    }

    public function end(): void
    {
        $this->current = null;
    }

    public function current(): ?LogEntry
    {
        return $this->current;
    }

    /**
     * Adds a line to the current entry, or to a standalone status entry when no action is resolving.
     */
    public function line(int $turn, ?int $actorId, string $text): void
    {
        if ($text === '') {
            return;
        }

        if ($this->current !== null) {
            $this->current->lines[] = $text;
            return;
        }

        $entry = new LogEntry($turn, $actorId, LogEntry::STATUS);
        $entry->lines[] = $text;
        $this->entries[] = $entry;
    }

    public function event(int $turn, BattleEvent $event): void
    {
        if ($this->current !== null) {
            $this->current->events[] = $event;
            return;
        }

        $entry = new LogEntry($turn, $event->payload['actor'] ?? null, LogEntry::STATUS);
        $entry->events[] = $event;
        $this->entries[] = $entry;
    }

    /**
     * @return LogEntry[]
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return LogEntry[]
     */
    public function flush(): array
    {
        $entries = $this->entries;
        $this->entries = [];
        $this->current = null;

        return $entries;
    }

    public function text(): string
    {
        return implode(' ', array_map(fn (LogEntry $entry) => $entry->description(), $this->entries));
    }
}
