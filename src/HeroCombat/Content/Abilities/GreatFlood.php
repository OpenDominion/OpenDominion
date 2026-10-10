<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

class GreatFlood extends AbstractDetonation
{
    public function key(): string
    {
        return 'great_flood';
    }

    public function name(): string
    {
        return 'Great Flood';
    }

    public function description(): string
    {
        return 'Strike all living enemies for 75% attack damage, bypassing all defenses.';
    }

    protected function multiplier(): float
    {
        return 0.75;
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} calls down a Great Flood, sweeping all enemies for {damage} damage each!',
            'no_targets' => '{actor} calls down a Great Flood, but no enemies remain!',
        ];
    }
}
