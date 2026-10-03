<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Stats\Modifier;

/**
 * A passive that grants stat modifiers, optionally only while health is low.
 */
abstract class AbstractStatPassive extends AbstractPassive
{
    public const LOW_HEALTH_THRESHOLD = 40;

    /**
     * @return Modifier[]
     */
    abstract protected function statModifiers(): array;

    protected function onlyWhenLowHealth(): bool
    {
        return false;
    }

    public function modifiers(EffectInstance $instance, CombatantState $subject, Battle $battle): array
    {
        if ($this->onlyWhenLowHealth() && $subject->currentHealth > self::LOW_HEALTH_THRESHOLD) {
            return [];
        }

        return $this->statModifiers();
    }
}
