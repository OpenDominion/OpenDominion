<?php

namespace OpenDominion\HeroCombat\Engine;

use OpenDominion\HeroCombat\Engine\Effects\Hook;

/**
 * Chooses each combatant's intent: forced by an effect, then the player's queue, then AI.
 * A queued or AI choice may then be replaced by an effect (e.g. Confused).
 */
final class IntentDecider
{
    public function __construct(private Battle $battle, private ActionValidator $validator)
    {
    }

    public function decide(CombatantState $combatant): Intent
    {
        $forced = $this->battle->dispatcher->first(Hook::ForcedIntent, [$combatant], $combatant, $this->battle);
        if ($forced instanceof Intent) {
            return $forced->withActor($combatant->id);
        }

        $intent = $this->choose($combatant);

        $altered = $this->battle->dispatcher->first(Hook::AlterIntent, [$combatant], $intent, $combatant, $this->battle);
        if ($altered instanceof Intent) {
            return $altered->replacing($intent);
        }

        return $intent;
    }

    private function choose(CombatantState $combatant): Intent
    {
        if ($combatant->isHuman() && count($combatant->queue) > 0) {
            $queued = array_shift($combatant->queue);
            if ($this->validator->canPerform($combatant, $queued['ability'])) {
                return new Intent($combatant->id, $queued['ability'], $queued['target'] ?? null, Intent::SOURCE_QUEUE);
            }
        }

        return $this->battle->registry->strategy($combatant->ai)->chooseIntent($combatant, $this->battle);
    }
}
