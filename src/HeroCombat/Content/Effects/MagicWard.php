<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\Hook;

/**
 * Halves damage taken. Runs before shields so they absorb the reduced amount.
 */
class MagicWard extends AbstractEffect
{
    public const DAMAGE_TAKEN_MULTIPLIER = 0.5;

    public function key(): string
    {
        return 'magic_ward';
    }

    public function name(): string
    {
        return 'Magic Ward';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Damage taken is halved.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function defaultDuration(): ?int
    {
        return 4;
    }

    public function handlerPriority(Hook $hook): int
    {
        return 20;
    }

    public function beforeDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        if ($damage->target->id !== $instance->ownerId || $damage->amount <= 0) {
            return;
        }

        $damage->amount = (int) round($damage->amount * self::DAMAGE_TAKEN_MULTIPLIER);
    }
}
