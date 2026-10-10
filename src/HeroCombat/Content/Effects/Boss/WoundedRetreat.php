<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * When the master falls, its summoned constructs collapse with it.
 */
class WoundedRetreat extends AbstractPassive
{
    public function key(): string
    {
        return 'wounded_retreat';
    }

    public function name(): string
    {
        return 'Wounded Retreat';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Upon defeat, this entity retreats across the planes rather than being destroyed.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $minions = $battle->alliesOf($dead, includeSelf: false);
        if ($minions === []) {
            return;
        }

        foreach ($minions as $minion) {
            $minion->currentHealth = 0;
        }

        $battle->say($dead, 'Without their master, the Void Constructs crumble and collapse!');
    }
}
