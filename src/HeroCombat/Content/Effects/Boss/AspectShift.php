<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * On death, a new form takes the fallen combatant's place on its team.
 *
 * Instance data: next (template key), name (display name, optional).
 */
class AspectShift extends AbstractPassive
{
    public function key(): string
    {
        return 'aspect_shift';
    }

    public function name(): string
    {
        return 'Aspect Shift';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Upon death, transforms into a new aspect.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $next = $instance->data['next'] ?? null;
        if ($next === null) {
            return;
        }

        $form = $battle->summon($next, $dead->team, $instance->data['name'] ?? null, $dead);
        $battle->say($dead, "{$dead->name} begins to shift form, transforming into {$form->name}!");
    }
}
