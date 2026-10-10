<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Nightfall extends AbstractCadence
{
    public const EVASION_CAP = 100;

    public function key(): string
    {
        return 'nightfall';
    }

    public function name(): string
    {
        return 'Darkness';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Every other turn, increases evasion value by 20.';
    }

    protected function abilityKey(): string
    {
        return 'darkness';
    }

    protected function defaultInterval(): int
    {
        return 2;
    }

    protected function warning(): string
    {
        return 'Darkness surrounds {actor}.';
    }

    protected function shouldFire(CombatantState $owner, Battle $battle): bool
    {
        return $battle->stat($owner, Stat::Evasion) < self::EVASION_CAP;
    }
}
