<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Ullyaoth's chest splits open as it Devours. The Titan Spear only finds its heart while this
 * lasts.
 */
class HeartExposed extends AbstractEffect
{
    public function key(): string
    {
        return 'heart_exposed';
    }

    public function name(): string
    {
        return 'Heart Exposed';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Its heart lies open this turn.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }
}
