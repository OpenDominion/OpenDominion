<?php

namespace OpenDominion\HeroCombat\Engine\Damage;

use OpenDominion\HeroCombat\Engine\CombatantState;

final readonly class DamageResult
{
    public function __construct(
        public CombatantState $target,
        public int $amount,
        public int $raw,
        public bool $evaded,
        public int $absorbed,
        public bool $savedFromLethal,
        public ?DamageResult $counter = null,
    ) {
    }

    public function counterDamage(): int
    {
        return $this->counter?->amount ?? 0;
    }

    public function wasCountered(): bool
    {
        return $this->counter !== null;
    }
}
