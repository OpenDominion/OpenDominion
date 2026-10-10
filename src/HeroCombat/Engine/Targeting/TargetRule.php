<?php

namespace OpenDominion\HeroCombat\Engine\Targeting;

enum TargetRule: string
{
    case Self = 'self';
    case SingleEnemy = 'single_enemy';
    case SingleAlly = 'single_ally';
    case SingleAny = 'single_any';
    case AllEnemies = 'all_enemies';
    case AllAllies = 'all_allies';
    case AllOthers = 'all_others';
    case RandomEnemy = 'random_enemy';

    /** Whether the player picks a target when queueing this ability. */
    public function needsChosenTarget(): bool
    {
        return in_array($this, [self::SingleEnemy, self::SingleAlly, self::SingleAny], true);
    }

    public function isHostile(): bool
    {
        return in_array($this, [self::SingleEnemy, self::AllEnemies, self::RandomEnemy, self::AllOthers], true);
    }
}
