<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;

/**
 * Corpseblood Feast's meal, resolved at the end of the turn after that turn's defeats: if the
 * champion still stands, it is devoured, healing its master and adding permanent attack.
 *
 * Instance data: champion (enemy template key).
 */
class Feasting extends AbstractEffect
{
    public const HEAL = 30;
    public const ATTACK_BONUS = 8;

    public function key(): string
    {
        return 'feasting';
    }

    public function name(): string
    {
        return 'Feasting';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Will devour its champion at the end of the turn if it still stands.';
    }

    public function defaultDuration(): ?int
    {
        return 1;
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive()) {
            return;
        }

        $template = $instance->data['champion'] ?? null;
        $champion = $template === null ? null : ($battle->withTemplate($template)[0] ?? null);

        if ($champion === null) {
            $battle->say($owner, "{$owner->name} finds nothing left to eat.");
            return;
        }

        $battle->heal($owner, self::HEAL, $owner, $this->key());
        $battle->effects->apply($owner, 'empowered', null, 1, ['bonuses' => ['attack' => self::ATTACK_BONUS]], $champion);
        $battle->setHealth($champion, 0);
        $battle->say($owner, "{$owner->name} devours {$champion->name} and grows stronger (attack +" . self::ATTACK_BONUS . ').');
    }
}
