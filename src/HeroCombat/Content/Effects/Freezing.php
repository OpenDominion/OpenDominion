<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Lasts until the end of the turn it is applied, then becomes Frozen for the next turn.
 */
class Freezing extends AbstractEffect
{
    public function key(): string
    {
        return 'freezing';
    }

    public function name(): string
    {
        return 'Freezing';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Will be frozen next turn.';
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
        return [CombatTag::Frost];
    }

    public function onExpire(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner !== null && $owner->isAlive()) {
            $battle->effects->apply($owner, 'frozen', null, 1, [], $battle->combatant($instance->sourceId));
        }
    }
}
