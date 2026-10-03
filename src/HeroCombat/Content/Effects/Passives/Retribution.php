<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Retribution extends AbstractStatPassive
{
    public function key(): string
    {
        return 'retribution';
    }

    public function name(): string
    {
        return 'Retribution';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Counter attack damage is increased by 15.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return false;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::flat(Stat::Counter, 15),
        ];
    }
}
