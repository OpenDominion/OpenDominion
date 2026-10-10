<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * Forces its holder to use an ability on a fixed schedule (turns 1, 1 + n, 1 + 2n, ...),
 * with a warning logged at the end of the turn before.
 *
 * Instance data: interval (int, optional) overrides defaultInterval(), e.g. for raid scaling.
 */
abstract class AbstractCadence extends AbstractPassive
{
    abstract protected function abilityKey(): string;

    abstract protected function defaultInterval(): int;

    /** Logged at the end of the turn before the ability fires; {actor} is replaced. */
    abstract protected function warning(): string;

    /** Extra condition for the ability to fire. */
    protected function shouldFire(CombatantState $owner, Battle $battle): bool
    {
        return true;
    }

    protected function interval(EffectInstance $instance): int
    {
        return max(1, (int) ($instance->data['interval'] ?? $this->defaultInterval()));
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId || ($battle->turn() - 1) % $this->interval($instance) !== 0) {
            return null;
        }

        if (!$this->shouldFire($subject, $battle)) {
            return null;
        }

        return new Intent($subject->id, $this->abilityKey(), null, Intent::SOURCE_FORCED);
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive() || $battle->turn() % $this->interval($instance) !== 0) {
            return;
        }

        if ($this->shouldFire($owner, $battle)) {
            $battle->say($owner, str_replace('{actor}', $owner->name, $this->warning()));
        }
    }
}
