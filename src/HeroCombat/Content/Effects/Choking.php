<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;
use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Doubled over by his own haze: cannot act and defense is lowered for a turn.
 */
class Choking extends AbstractEffect
{
    public const DEFENSE_PENALTY = 10;

    public function key(): string
    {
        return 'choking';
    }

    public function name(): string
    {
        return 'Choking';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Cannot act. Defense reduced by ' . self::DEFENSE_PENALTY . '.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function tags(): array
    {
        return [CombatTag::Stunned];
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        return [Modifier::flat(Stat::Defense, -self::DEFENSE_PENALTY)];
    }
}
