<?php

namespace OpenDominion\HeroCombat\Engine\Random;

interface RandomSource
{
    /** Inclusive on both ends. */
    public function int(int $min, int $max): int;

    /** True with the given probability (0-1). */
    public function chance(float $probability): bool;

    /**
     * @template T
     * @param array<int|string, T> $items
     * @return T
     */
    public function pick(array $items): mixed;

    /**
     * @param array<string, int|float> $weights
     */
    public function weighted(array $weights): string;
}
