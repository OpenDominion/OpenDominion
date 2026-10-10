<?php

namespace OpenDominion\HeroCombat\Content\Effects\Stances;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * A one-turn posture applied when the stance ability is declared, so every action in the
 * turn sees it regardless of resolution order.
 */
abstract class AbstractStance extends AbstractEffect
{
    public function kind(): EffectKind
    {
        return EffectKind::Status;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function dispellable(): bool
    {
        return false;
    }
}
