<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * When the holder dies, every living combatant with the linked template is Severed.
 *
 * Instance data: target (template key), reductions (stat => amount).
 */
class PowerSource extends AbstractPassive
{
    public function key(): string
    {
        return 'power_source';
    }

    public function name(): string
    {
        return 'Power Source';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Upon destruction, weakens a connected ally.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $reductions = $instance->data['reductions'] ?? [];
        $targets = $battle->withTemplate($instance->data['target'] ?? '');
        if ($reductions === [] || $targets === []) {
            return;
        }

        $changes = implode(', ', array_map(fn ($stat, $amount) => "{$stat} -{$amount}", array_keys($reductions), $reductions));

        foreach ($targets as $target) {
            $battle->effects->apply($target, 'severed', null, 1, ['reductions' => $reductions], $dead);
            $battle->say($dead, "{$dead->name} crumbles to dust, severing its connection to {$target->name} ({$changes})!");
        }
    }
}
