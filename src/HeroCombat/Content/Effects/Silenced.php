<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Grants the Silenced tag, which blocks Focus and Recover.
 */
class Silenced extends AbstractEffect
{
    public function key(): string
    {
        return 'silenced';
    }

    public function name(): string
    {
        return 'Silenced';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Cannot Focus or Recover.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 3;
    }

    public function tags(): array
    {
        return [CombatTag::Silenced];
    }
}
