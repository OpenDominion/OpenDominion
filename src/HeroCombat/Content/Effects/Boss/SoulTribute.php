<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * On death, empowers every living combatant with the linked template.
 *
 * Instance data: target (template key), bonuses (stat => amount).
 */
class SoulTribute extends AbstractPassive
{
    public function key(): string
    {
        return 'soul_tribute';
    }

    public function name(): string
    {
        return 'Soul Tribute';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Upon death, empowers its master, increasing his attack and defense.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $bonuses = $instance->data['bonuses'] ?? [];
        if ($bonuses === []) {
            return;
        }

        $changes = implode(', ', array_map(fn ($stat, $amount) => "{$stat} +{$amount}", array_keys($bonuses), $bonuses));

        foreach ($battle->withTemplate($instance->data['target'] ?? '') as $target) {
            $battle->effects->apply($target, 'empowered', null, 1, ['bonuses' => $bonuses], $dead);
            $battle->say($dead, "As {$dead->name} falls, dark tendrils stream from the body into {$target->name} ({$changes}). He grows stronger.");
        }
    }
}
