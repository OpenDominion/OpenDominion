<?php

namespace OpenDominion\HeroCombat\Content\Abilities;

use OpenDominion\HeroCombat\Engine\ActionContext;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Damage\DamageRequest;

class VolatileMixture extends AbstractAttack
{
    public const SUCCESS_CHANCE = 0.8;
    public const DAMAGE_MULTIPLIER = 1.5;

    public function key(): string
    {
        return 'volatile_mixture';
    }

    public function name(): string
    {
        return 'Volatile Mixture';
    }

    public function description(): string
    {
        return 'Attack for 150% damage, but 20% chance to hit yourself.';
    }

    public function cooldown(CombatantState $actor, Battle $battle): int
    {
        return 1;
    }

    public function resolve(ActionContext $context): void
    {
        $target = $context->target();

        if ($context->battle->random->chance(self::SUCCESS_CHANCE)) {
            $result = $context->attack($target, ['multiplier' => self::DAMAGE_MULTIPLIER]);
            static::narrate($context, $result);
            return;
        }

        $backfire = $context->dealDamage(new DamageRequest(
            attacker: $context->actor,
            target: $context->actor,
            abilityKey: $this->key(),
            canEvade: false,
            canBeCountered: false,
            ignoreShield: true,
            tags: [],
        ));
        $context->say('backfire', ['damage' => $backfire->amount]);

        if ($context->battle->hasTag($target, CombatTag::Countering)) {
            $counter = $context->battle->damage->counterAttack($target, $context->actor);
            $context->battle->say($context->actor, "{$target->name} counters the distracted alchemist for {$counter->amount} damage.");
        }
    }

    public function messages(): array
    {
        return [
            'hit' => '{actor} hurls an unstable concoction, dealing {damage} damage to {target}.',
            'evaded' => '{actor}\'s explosive mixture detonates, but {target} evades most of the blast, taking only {damage} damage.',
            'backfire' => '{actor}\'s volatile mixture explodes prematurely! {actor} is caught in the blast, taking {damage} damage.',
        ];
    }
}
