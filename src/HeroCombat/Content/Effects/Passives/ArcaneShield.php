<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class ArcaneShield extends AbstractStatPassive
{
    public function key(): string
    {
        return 'arcane_shield';
    }

    public function name(): string
    {
        return 'Arcane Shield';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Defense value is increased by 10.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return false;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::flat(Stat::Defense, 10),
        ];
    }
}
