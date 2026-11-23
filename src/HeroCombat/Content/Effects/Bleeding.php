<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Effects\StackingRule;

/**
 * Takes DAMAGE per stack at the end of every turn, the turn it is applied included. Each new
 * wound adds a stack (up to MAX_STACKS) and restarts the duration. A guard can't take it.
 */
class Bleeding extends AbstractEffect
{
    public const DAMAGE = 5;
    public const TURNS = 3;
    public const MAX_STACKS = 3;

    public function key(): string
    {
        return 'bleeding';
    }

    public function name(): string
    {
        return 'Bleeding';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Loses ' . (self::DAMAGE * $instance->stacks) . ' health at the end of each turn.';
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function stacking(): StackingRule
    {
        return StackingRule::Stack;
    }

    public function maxStacks(Battle $battle, ?CombatantState $owner): ?int
    {
        return self::MAX_STACKS;
    }

    public function defaultDuration(): ?int
    {
        return self::TURNS;
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive()) {
            return;
        }

        $battle->damage->resolve(new DamageRequest(
            attacker: null,
            target: $owner,
            abilityKey: $this->key(),
            flatDamage: self::DAMAGE * $instance->stacks,
            canEvade: false,
            canBeCountered: false,
            tags: [],
            canBeRedirected: false,
        ));
    }
}
