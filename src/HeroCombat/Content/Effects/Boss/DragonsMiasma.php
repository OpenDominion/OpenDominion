<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Content\Effects\Confused;
use OpenDominion\HeroCombat\Content\Effects\Passives\ClearMinded;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * Waaugh gathers the haze at the end of one turn and exhales it at the end of the next, as a
 * free action. Heroes who are Focused shrug it off; everyone else is Confused. If nobody is
 * confused, the haze chokes Waaugh instead. While any hero is Confused, Waaugh casts Bone
 * Splinter at them.
 *
 * Instance data: gathering (bool).
 */
class DragonsMiasma extends AbstractPassive
{
    public const PERIOD = 3;
    public const GATHER_ON = 1;
    public const EXHALE_ON = 2;

    /**
     * Effects applied by a TurnEnd hook are ticked in that same end-of-turn pass, so they need
     * one extra turn to last through the turns that follow.
     */
    public const CONFUSED_TURNS = 2 + 1;
    public const CHOKING_TURNS = 1 + 1;

    public function key(): string
    {
        return 'dragons_miasma';
    }

    public function name(): string
    {
        return 'Dragon\'s Miasma';
    }

    public function description(EffectInstance $instance): string
    {
        return 'Every third turn, exhales a haze that clouds the minds of those who breathe it.';
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

        $phase = $battle->turn() % self::PERIOD;

        if ($phase === self::GATHER_ON) {
            $instance->data['gathering'] = true;
            $battle->say($owner, "{$owner->name} lifts a pipe of carved dragon bone to his lips. A silver haze gathers in the bowl, sweet and thick.");
            return;
        }

        if ($phase === self::EXHALE_ON && !empty($instance->data['gathering'])) {
            unset($instance->data['gathering']);
            $this->exhale($owner, $battle);
        }
    }

    protected function exhale(CombatantState $owner, Battle $battle): void
    {
        $battle->say($owner, "{$owner->name} exhales, and the silver haze rolls out across the cavern.");

        $heroes = $battle->enemiesOf($owner);
        $confused = 0;

        foreach ($heroes as $hero) {
            if ($battle->hasTag($hero, CombatTag::Focused)) {
                $battle->say($hero, "{$hero->name} keeps their mind on the fight. The haze finds nothing to take hold of.");
                continue;
            }

            $chance = $battle->effects->has($hero, 'clear_minded') ? ClearMinded::CONFUSION_CHANCE : Confused::DEFAULT_CHANCE;
            $battle->effects->apply($hero, 'confused', self::CONFUSED_TURNS, 1, ['chance' => $chance], $owner);
            $battle->say($hero, "The haze seeps into {$hero->name}'s lungs. Their thoughts scatter.");
            $confused++;
        }

        if ($heroes !== [] && $confused === 0) {
            $battle->effects->apply($owner, 'choking', self::CHOKING_TURNS, 1, [], $owner);
            $battle->say($owner, "With no one to take it, the haze curls back into {$owner->name}'s lungs. He doubles over, choking.");
        }
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        if ($subject->id !== $instance->ownerId || $battle->hasTag($subject, CombatTag::Stunned)) {
            return null;
        }

        $confused = array_values(array_filter(
            $battle->enemiesOf($subject),
            fn (CombatantState $hero) => $battle->hasTag($hero, CombatTag::Confused),
        ));

        if ($confused === []) {
            return null;
        }

        $target = count($confused) === 1 ? $confused[0] : $battle->random->pick($confused);

        return new Intent($subject->id, 'bone_splinter', $target->id, Intent::SOURCE_FORCED);
    }
}
