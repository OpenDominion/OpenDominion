<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * Ullyaoth's two telegraphs. At the end of a turn it may rain blood on a hero (alternating
 * between heroes) and Devour them on the next turn; Devours are at least DEVOUR_MIN_GAP turns
 * apart. While its champion stands it may also eye it hungrily,
 * then spend the next turn on Corpseblood Feast. A Feast is never told for a Devour turn.
 *
 * Instance data: champion (enemy template key), devour_turn, target, last_target, feast_turn.
 */
class BloodRain extends AbstractPassive
{
    public const DEVOUR_MIN_GAP = 3;
    public const DEVOUR_CHANCE = 0.7;
    public const FEAST_CHANCE = 0.35;

    public function key(): string
    {
        return 'blood_rain';
    }

    public function name(): string
    {
        return 'Blood Rain';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Blood rains on a hero before Ullyaoth parts the clouds to devour them.';
    }

    public function tags(): array
    {
        return [CombatTag::Telegraph];
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive()) {
            return;
        }

        $this->maybeTellDevour($instance, $owner, $battle);
        $this->maybeTellFeast($instance, $owner, $battle);
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId) {
            return null;
        }

        $turn = $battle->turn();

        if (($instance->data['devour_turn'] ?? null) === $turn) {
            $target = $battle->combatant($instance->data['target'] ?? null);
            if ($target === null || !$target->isAlive()) {
                $living = $battle->enemiesOf($subject);
                $target = $living === [] ? null : $battle->random->pick(array_values($living));
            }

            return new Intent($subject->id, 'devour', $target?->id, Intent::SOURCE_FORCED);
        }

        if (($instance->data['feast_turn'] ?? null) === $turn) {
            return new Intent($subject->id, 'corpseblood_feast', $subject->id, Intent::SOURCE_FORCED);
        }

        return null;
    }

    /**
     * Whether a combatant on the other side of `$viewer` is Devouring this turn.
     */
    public static function isDevourTurn(Battle $battle, CombatantState $viewer): bool
    {
        foreach ($battle->enemiesOf($viewer) as $enemy) {
            $instance = $battle->effects->find($enemy, 'blood_rain');
            if ($instance !== null && ($instance->data['devour_turn'] ?? null) === $battle->turn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * The living champion the holder can feast on, if any.
     */
    public static function champion(EffectInstance $instance, Battle $battle): ?CombatantState
    {
        $template = $instance->data['champion'] ?? null;

        return $template === null ? null : ($battle->withTemplate($template)[0] ?? null);
    }

    protected function maybeTellDevour(EffectInstance $instance, CombatantState $owner, Battle $battle): void
    {
        $turn = $battle->turn();
        $devourTurn = $turn + 1;

        if (($instance->data['devour_turn'] ?? 0) > $turn) {
            return;
        }
        if (isset($instance->data['devour_turn']) && $devourTurn - $instance->data['devour_turn'] < self::DEVOUR_MIN_GAP) {
            return;
        }
        if (!$battle->random->chance(self::DEVOUR_CHANCE)) {
            return;
        }

        $heroes = array_values($battle->enemiesOf($owner));
        if ($heroes === []) {
            return;
        }

        $last = $instance->data['last_target'] ?? null;
        $others = array_values(array_filter($heroes, fn (CombatantState $hero) => $hero->id !== $last));
        $target = $battle->random->pick($others === [] ? $heroes : $others);

        $instance->data['devour_turn'] = $devourTurn;
        $instance->data['target'] = $target->id;
        $instance->data['last_target'] = $target->id;
        $battle->say($owner, "Blood begins to rain on {$target->name}.");
    }

    protected function maybeTellFeast(EffectInstance $instance, CombatantState $owner, Battle $battle): void
    {
        $turn = $battle->turn();
        $champion = self::champion($instance, $battle);

        if ($champion === null || ($instance->data['feast_turn'] ?? 0) >= $turn) {
            return;
        }
        if (($instance->data['devour_turn'] ?? null) === $turn + 1) {
            return;
        }
        if (!$battle->random->chance(self::FEAST_CHANCE)) {
            return;
        }

        $instance->data['feast_turn'] = $turn + 1;
        $battle->say($owner, "{$owner->name}'s hungry gaze falls on {$champion->name}.");
    }
}
