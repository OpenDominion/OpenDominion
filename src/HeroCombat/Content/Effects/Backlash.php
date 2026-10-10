<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Damage\DamageContext;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;

/**
 * Attackers take a share of the damage they deal. It does not reduce the damage the owner takes.
 */
class Backlash extends AbstractEffect
{
    public const REFLECTED_SHARE = 0.5;

    public function key(): string
    {
        return 'backlash';
    }

    public function name(): string
    {
        return 'Backlash';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Attackers take ' . (self::REFLECTED_SHARE * 100) . '% of the damage they deal.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Buff;
    }

    public function defaultDuration(): ?int
    {
        return 3;
    }

    public function afterDamageTaken(EffectInstance $instance, DamageContext $damage, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        $attacker = $damage->attacker();

        if ($owner === null || $attacker === null || $damage->target->id !== $owner->id) {
            return;
        }

        if ($attacker->team === $owner->team || !$attacker->isAlive() || $damage->request->abilityKey === $this->key()) {
            return;
        }

        $reflected = (int) round($damage->amount * self::REFLECTED_SHARE);
        if ($reflected <= 0) {
            return;
        }

        $result = $battle->damage->resolve(new DamageRequest(
            attacker: $owner,
            target: $attacker,
            abilityKey: $this->key(),
            flatDamage: $reflected,
            canEvade: false,
            canBeCountered: false,
            tags: [],
        ));

        $battle->say($owner, "Backlash rebounds on {$attacker->name} for {$result->amount} damage.");
    }
}
