<?php

namespace OpenDominion\HeroCombat\Content\Effects\Stances;

use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

class Countering extends AbstractStance
{
    public function key(): string
    {
        return 'countering';
    }

    public function name(): string
    {
        return 'Countering';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Strikes back at attackers this turn.';
    }

    public function tags(): array
    {
        return [CombatTag::Countering];
    }
}
