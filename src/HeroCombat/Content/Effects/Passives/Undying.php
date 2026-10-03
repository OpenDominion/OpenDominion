<?php

namespace OpenDominion\HeroCombat\Content\Effects\Passives;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Returns from the dead with half its maximum health a few turns after falling.
 *
 * Instance data: countdown (int) turns left while dead; died_on (int) the turn it fell.
 */
class Undying extends AbstractPassive
{
    public const TURNS = 5;

    public function key(): string
    {
        return 'undying';
    }

    public function name(): string
    {
        return 'Undying';
    }

    public function description(EffectInstance $instance): string
    {
        return isset($instance->data['countdown'])
            ? "Returns from the dead in {$instance->data['countdown']} turns."
            : 'Returns from the dead ' . self::TURNS . ' turns after being defeated.';
    }

    public function onDeath(EffectInstance $instance, CombatantState $dead, Battle $battle): void
    {
        $instance->data['countdown'] = self::TURNS;
        $instance->data['died_on'] = $battle->turn();
        $battle->say($dead, "{$dead->name} will return from the dead in " . self::TURNS . ' turns.');
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || $owner->isAlive() || !isset($instance->data['countdown'])) {
            return;
        }

        if (($instance->data['died_on'] ?? null) === $battle->turn()) {
            return;
        }

        $instance->data['countdown']--;

        if ($instance->data['countdown'] > 0) {
            $battle->say($owner, "{$owner->name} will return from the dead in {$instance->data['countdown']} turns.");
            return;
        }

        unset($instance->data['countdown'], $instance->data['died_on']);
        $health = (int) round($battle->maxHealth($owner) / 2);
        $battle->effects->removeByKey($owner, 'focused');
        $battle->revive($owner, $health, $health);
        $battle->say($owner, "{$owner->name} has returned to life.");
    }
}
