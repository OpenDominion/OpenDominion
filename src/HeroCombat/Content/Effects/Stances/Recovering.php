<?php

namespace OpenDominion\HeroCombat\Content\Effects\Stances;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

class Recovering extends AbstractStance
{
    public const DEFENSE_PENALTY = 5;

    public function key(): string
    {
        return 'recovering';
    }

    public function name(): string
    {
        return 'Recovering';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Defense reduced by ' . self::DEFENSE_PENALTY . ' this turn.';
    }

    public function tags(): array
    {
        return [CombatTag::Recovering];
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [Modifier::flat(Stat::Defense, -self::DEFENSE_PENALTY)];
    }
}
