<?php

namespace OpenDominion\HeroCombat\Engine\Victory;

final readonly class VictoryResult
{
    public function __construct(public ?int $winningTeam)
    {
    }

    public function isDraw(): bool
    {
        return $this->winningTeam === null;
    }
}
