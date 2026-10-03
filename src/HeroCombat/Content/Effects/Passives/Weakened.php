<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Weakened extends AbstractStatPassive
{
    public function key(): string
    {
        return 'weakened';
    }

    public function name(): string
    {
        return 'Weakened';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Defense value is decreased by 15.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return false;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::flat(Stat::Defense, -15),
        ];
    }
}
