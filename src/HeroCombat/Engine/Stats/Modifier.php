<?php

namespace OpenDominion\HeroCombat\Engine\Stats;

/**
 * A single adjustment to one stat, contributed by an effect.
 */
final readonly class Modifier
{
    public function __construct(
        public Stat $stat,
        public ModifierType $type,
        public float $value,
    ) {
    }

    public static function flat(Stat $stat, float $value): self
    {
        return new self($stat, ModifierType::Flat, $value);
    }

    public static function percent(Stat $stat, float $value): self
    {
        return new self($stat, ModifierType::Percent, $value);
    }

    public static function override(Stat $stat, float $value): self
    {
        return new self($stat, ModifierType::Override, $value);
    }
}
