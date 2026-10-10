<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Wide open while channeling: -5 defense for the turn.
 */
class Exposed extends AbstractEffect
{
    public const DEFENSE_PENALTY = 5;

    public function key(): string
    {
        return 'exposed';
    }

    public function name(): string
    {
        return 'Exposed';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Defense reduced by ' . self::DEFENSE_PENALTY . ' while casting.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [Modifier::flat(Stat::Defense, -self::DEFENSE_PENALTY)];
    }
}
