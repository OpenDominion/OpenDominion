<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Enrage extends AbstractStatPassive
{
    public function key(): string
    {
        return 'enrage';
    }

    public function name(): string
    {
        return 'Enrage';
    }

    public function description(EffectInstance $instance): string
    {
        return 'When at 40 health or less, attack value is increased by 10.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return true;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::flat(Stat::Attack, 10),
        ];
    }
}
