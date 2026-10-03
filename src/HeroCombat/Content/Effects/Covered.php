<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Hits aimed at the holder are taken by the guardian who applied this effect instead.
 */
class Covered extends AbstractEffect
{
    public function key(): string
    {
        return 'covered';
    }

    public function name(): string
    {
        return 'Covered';
    }

    public function description(EffectInstance $instance): string
    {
        return 'An ally is taking hits in your place.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function redirectDamage(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $guardian = $battle->combatant($instance->sourceId);
        if ($guardian === null || !$guardian->isAlive() || $damage->target !== $this->owner($instance, $battle)) {
            return;
        }

        $battle->say($guardian, "{$guardian->name} steps in front of {$damage->target->name}.");
        $damage->target = $guardian;
    }
}
