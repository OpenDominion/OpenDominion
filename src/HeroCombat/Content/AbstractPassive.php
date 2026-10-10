<?php

namespace OpenDominion\HeroCombat\Content;

use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Innate effects: permanent, not dispellable. Class passives and boss traits.
 */
abstract class AbstractPassive extends AbstractEffect
{
    public function kind(): EffectKind
    {
        return EffectKind::Innate;
    }
}
