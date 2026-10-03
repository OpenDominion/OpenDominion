<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Single-target hostile abilities must target the combatant that applied this effect.
 */
class Provoked extends AbstractEffect
{
    public function key(): string
    {
        return 'provoked';
    }

    public function name(): string
    {
        return 'Provoked';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Must attack the provoker.';
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
        return [CombatTag::Provoked];
    }
}
