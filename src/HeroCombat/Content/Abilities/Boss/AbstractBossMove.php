<?php

namespace OpenDominion\HeroCombat\Content\Abilities\Boss;

use OpenDominion\HeroCombat\Content\AbstractAbility;

/**
 * Signature moves performed through telegraphs; never chosen from a menu or by weight.
 */
abstract class AbstractBossMove extends AbstractAbility
{
    public function selectable(): bool
    {
        return false;
    }

    public function description(): string
    {
        return $this->name();
    }
}
