<?php

namespace OpenDominion\Tests\Unit\HeroCombat\Support;

use OpenDominion\HeroCombat\Engine\Random\RandomSource;

/**
 * Deterministic randomness for tests: evasion never triggers and chances always succeed
 * unless a test queues specific values.
 */
class ScriptedRandomSource implements RandomSource
{
    /** @var int[] */
    public array $ints = [];

    /** @var bool[] */
    public array $chances = [];

    /** @var string[] */
    public array $weightedPicks = [];

    public int $defaultInt = 100;

    public bool $defaultChance = true;

    public function int(int $min, int $max): int
    {
        $value = array_shift($this->ints) ?? $this->defaultInt;

        return max($min, min($max, $value));
    }

    public function chance(float $probability): bool
    {
        return array_shift($this->chances) ?? $this->defaultChance;
    }

    public function pick(array $items): mixed
    {
        return array_values($items)[0];
    }

    public function weighted(array $weights): string
    {
        $pick = array_shift($this->weightedPicks);
        if ($pick !== null && isset($weights[$pick])) {
            return $pick;
        }

        return (string) array_key_first(array_filter($weights, fn ($w) => $w > 0));
    }
}
