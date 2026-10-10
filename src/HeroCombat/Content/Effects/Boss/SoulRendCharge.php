<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * When wounded, charges Soul Rend at the end of a turn and unleashes it the next. It charges
 * again each time it takes further damage below the threshold.
 *
 * Instance data: charging (bool), last_health (int).
 */
class SoulRendCharge extends AbstractPassive
{
    public const THRESHOLD = 40;

    public function key(): string
    {
        return 'soul_rend_charge';
    }

    public function name(): string
    {
        return 'Soul Rend';
    }

    public function description(EffectInstance $instance): string
    {
        return 'When wounded, charges a devastating attack that deals massive damage if not defended.';
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive() || $owner->currentHealth > self::THRESHOLD || !empty($instance->data['charging'])) {
            return;
        }

        $lastHealth = $instance->data['last_health'] ?? null;
        if ($lastHealth !== null && $owner->currentHealth >= $lastHealth) {
            return;
        }

        $instance->data['charging'] = true;
        $instance->data['last_health'] = $owner->currentHealth;
        $battle->say($owner, "{$owner->name}'s form begins to glow with gathering ethereal energy... defend yourself!");
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId || empty($instance->data['charging'])) {
            return null;
        }

        unset($instance->data['charging']);

        return new Intent($subject->id, 'soul_rend', null, Intent::SOURCE_FORCED);
    }
}
