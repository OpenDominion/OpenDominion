<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * On death, strips the darkness from every combatant with the linked template.
 *
 * Instance data: target (template key, default nightbringer).
 */
class DyingLight extends AbstractPassive
{
    public function key(): string
    {
        return 'dying_light';
    }

    public function name(): string
    {
        return 'Dying Light';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Upon death, reduces the Nightbringer\'s evasion to 0.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $targets = $battle->withTemplate($instance->data['target'] ?? 'nightbringer');
        if ($targets === []) {
            $battle->say($dead, "{$dead->name} explodes in a blast of light.");
            return;
        }

        foreach ($targets as $target) {
            $battle->effects->removeByKey($target, 'shrouded');
            $battle->say($dead, "{$dead->name} explodes in a blast of light, exposing {$target->name}!");
        }
    }
}
