<?php

namespace OpenDominion\HeroCombat\Content\Effects\Boss;

use OpenDominion\HeroCombat\Content\AbstractPassive;
use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;
use OpenDominion\HeroCombat\Engine\CombatTag;
use OpenDominion\HeroCombat\Engine\Effects\EffectInstance;
use OpenDominion\HeroCombat\Engine\Intent;

/**
 * A boss trait that announces its next signature move (the "tell") at the end of one turn
 * and performs it on a later turn, giving players a chance to answer it.
 *
 * Instance data: pending (string|null) — the move that has been telegraphed.
 */
abstract class AbstractTelegraph extends AbstractPassive
{
    /**
     * @return string[] ability keys that can be telegraphed
     */
    abstract protected function moves(): array;

    /**
     * Flavor text announcing each move. Must not name the correct counter-play.
     *
     * @return array<string, string> ability key => message with {actor}
     */
    abstract protected function tells(): array;

    /** Telegraph on turns where turn % period equals this. */
    protected function telegraphOn(): int
    {
        return 1;
    }

    /** Perform on turns where turn % period equals this. */
    protected function performOn(): int
    {
        return 0;
    }

    protected function period(): int
    {
        return 2;
    }

    public function tags(): array
    {
        return [CombatTag::Telegraph];
    }

    public function description(EffectInstance $instance): string
    {
        return $this->name() . ': every other turn, telegraphs a signature move one turn before it lands.';
    }

    /**
     * Picks the next move. Override for weighted or conditional choices.
     */
    protected function chooseMove(EffectInstance $instance, CombatantState $owner, Battle $battle): ?string
    {
        return $battle->random->pick($this->moves());
    }

    public function onTurnEnd(EffectInstance $instance, Battle $battle): void
    {
        $owner = $this->owner($instance, $battle);
        if ($owner === null || !$owner->isAlive() || !empty($instance->data['pending'])) {
            return;
        }

        if ($battle->turn() % $this->period() !== $this->telegraphOn()) {
            return;
        }

        $move = $this->chooseMove($instance, $owner, $battle);
        if ($move === null) {
            return;
        }

        $instance->data['pending'] = $move;
        $battle->say($owner, str_replace('{actor}', $owner->name, $this->tells()[$move] ?? ''));
    }

    public function forcedIntent(EffectInstance $instance, CombatantState $subject, Battle $battle): ?Intent
    {
        $move = $instance->data['pending'] ?? null;
        if ($move === null || $subject->id !== $instance->ownerId || $battle->turn() % $this->period() !== $this->performOn()) {
            return null;
        }

        unset($instance->data['pending']);

        return new Intent($subject->id, $move, null, Intent::SOURCE_FORCED);
    }

    /**
     * The move telegraphed for the coming turn, if any.
     */
    public static function pending(EffectInstance $instance): ?string
    {
        return $instance->data['pending'] ?? null;
    }
}
