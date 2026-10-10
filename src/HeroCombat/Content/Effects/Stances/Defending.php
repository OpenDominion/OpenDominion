<?php

namespace OpenDominion\HeroCombat\Content\Effects\Stances;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Defending extends AbstractStance
{
    public function key(): string
    {
        return 'defending';
    }

    public function name(): string
    {
        return 'Defending';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Defense doubled this turn.';
    }

    public function tags(): array
    {
        return [CombatTag::Defending];
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [Modifier::percent(Stat::Defense, 1.0)];
    }
}
