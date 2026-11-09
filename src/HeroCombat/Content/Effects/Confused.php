<?php

namespace OpenDominion\HeroCombat\Content\Effects;

use OpenDominion\HeroCombat\Content\AbstractEffect;
use OpenDominion\HeroCombat\Engine\ActionValidator;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Effects\EffectKind;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * Each action has a chance to be replaced by a different one, weighted toward Defend.
 * Never replaces with the chosen ability or with Focus, and never alters a forced action.
 *
 * Instance data: chance (float, optional) overrides DEFAULT_CHANCE.
 */
class Confused extends AbstractEffect
{
    public const DEFAULT_CHANCE = 0.5;

    /** @var array<string, int> ability key => weight */
    public const WEIGHTS = [
        'defend' => 50,
        'recover' => 25,
        'counter' => 15,
        'attack' => 10,
    ];

    public function key(): string
    {
        return 'confused';
    }

    public function name(): string
    {
        return 'Confused';
    }

    public function description(EffectInstance $instance): string
    {
        $percent = (int) round(self::chance($instance) * 100);

        return "Each action has a {$percent}% chance to be replaced by another.";
    }

    public function kind(): EffectKind
    {
        return EffectKind::Debuff;
    }

    public function defaultDuration(): ?int
    {
        return 2;
    }

    public function tags(): array
    {
        return [CombatTag::Confused];
    }

    public static function chance(EffectInstance $instance): float
    {
        return (float) ($instance->data['chance'] ?? self::DEFAULT_CHANCE);
    }

    public function alterIntent(EffectInstance $instance, Intent $intent, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId || $intent->source === Intent::SOURCE_FORCED) {
            return null;
        }

        if (!$battle->random->chance(self::chance($instance))) {
            return null;
        }

        $validator = new ActionValidator($battle);
        $options = array_filter(
            self::WEIGHTS,
            fn (int $weight, string $abilityKey) => $abilityKey !== $intent->abilityKey && $validator->canPerform($subject, $abilityKey),
            ARRAY_FILTER_USE_BOTH,
        );

        if ($options === []) {
            return null;
        }

        $abilityKey = $battle->random->weighted($options);
        $intended = $battle->registry->ability($intent->abilityKey)->name();
        $actual = $battle->registry->ability($abilityKey)->name();
        $battle->say($subject, "Lost in the haze, {$subject->name} meant to {$intended} but uses {$actual} instead.");

        return new Intent($subject->id, $abilityKey);
    }

    public function onExpire(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner !== null && $owner->isAlive()) {
            $battle->say($owner, "The fog lifts, and {$owner->name}'s mind regains clarity.");
        }
    }
}
