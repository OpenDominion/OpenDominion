<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

class Demolish extends AbstractDetonation
{
    public function key(): string
    {
        return 'demolish';
    }

    public function name(): string
    {
        return 'Demolish';
    }

    public function description(): string
    {
        return 'Detonate explosives against all enemies for 75% attack damage, bypassing all defenses.';
    }

    protected function multiplier(): float
    {
        return 0.75;
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} detonates a chain of precision charges, dealing {damage} damage to all enemies!',
            'no_targets' => '{actor} primes the charges, but no enemies remain!',
        ];
    }
}
