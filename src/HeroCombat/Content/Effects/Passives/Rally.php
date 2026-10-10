<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Rally extends AbstractStatPassive
{
    public function key(): string
    {
        return 'rally';
    }

    public function name(): string
    {
        return 'Rally';
    }

    public function description(EffectInstance $instance): string
    {
        return 'When at 40 health or less, defense value is increased by 5.';
    }

    protected function onlyWhenLowHealth(): bool
    {
        return true;
    }

    protected function statModifiers(): array
    {
        return [
            Modifier::flat(Stat::Defense, 5),
        ];
    }
}
