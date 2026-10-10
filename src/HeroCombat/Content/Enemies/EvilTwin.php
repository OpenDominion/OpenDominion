<?php

namespace OpenDominion\HeroCombat\Content\Enemies;

use OpenDominion\HeroCombat\Content\AbstractEnemy;

/**
 * A copy of the challenger; the encounter overrides its stats with the player's own.
 */
class EvilTwin extends AbstractEnemy
{
    public function key(): string
    {
        return 'evil_twin';
    }

    public function name(): string
    {
        return 'Evil Twin';
    }

    public function stats(): array
    {
        return [
            'health' => 60,
            'attack' => 40,
            'defense' => 20,
            'evasion' => 10,
            'focus' => 10,
            'counter' => 10,
            'recover' => 20,
        ];
    }
}
