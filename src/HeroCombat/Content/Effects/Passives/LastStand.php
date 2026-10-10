<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class LastStand extends AbstractStatPassive
{
    public function key(): string
    {
        return 'last_stand';
    }

    public function name(): string
    {
        return 'Last Stand';
    }

    public function description(EffectInstance $instance): string
    {
        return 'When at 40 health or less, all combat stats are increased by 10%.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return true;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::percent(Stat::Attack, 0.1),
            Modifier::percent(Stat::Defense, 0.1),
            Modifier::percent(Stat::Evasion, 0.1),
            Modifier::percent(Stat::Focus, 0.1),
            Modifier::percent(Stat::Counter, 0.1),
            Modifier::percent(Stat::Recover, 0.1),
        ];
    }
}
