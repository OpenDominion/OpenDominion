<?php

namespace OpenDominion\HeroCombat\Engine\Random;

use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * Deterministic randomness. Production battles seed one of these per turn from the
 * battle seed, which makes every battle replayable from its stored intents.
 */
final class SeededRandomSource implements RandomSource
{
    private Randomizer $randomizer;

    public function __construct(int $seed)
    {
        $this->randomizer = new Randomizer(new Mt19937($seed));
    }

    public static function forTurn(int $battleSeed, int $turn): self
    {
        return new self(crc32("{$battleSeed}:{$turn}"));
    }

    public function int(int $min, int $max): int
    {
        return $this->randomizer->getInt($min, $max);
    }

    public function chance(float $probability): bool
    {
        if ($probability <= 0) {
            return false;
        }
        if ($probability >= 1) {
            return true;
        }

        return $this->randomizer->getFloat(0, 1) < $probability;
    }

    public function pick(array $items): mixed
    {
        $values = array_values($items);

        return $values[$this->int(0, count($values) - 1)];
    }

    public function weighted(array $weights): string
    {
        $weights = array_filter($weights, fn ($weight) => $weight > 0);
        $total = array_sum($weights);
        $roll = $this->randomizer->getFloat(0, $total);

        foreach ($weights as $choice => $weight) {
            $roll -= $weight;
            if ($roll < 0) {
                return (string) $choice;
            }
        }

        return (string) array_key_last($weights);
    }
}
