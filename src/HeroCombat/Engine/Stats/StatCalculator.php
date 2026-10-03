<?php

namespace OpenDominion\HeroCombat\Engine\Stats;

use OpenDominion\HeroCombat\Engine\Battle;
use OpenDominion\HeroCombat\Engine\CombatantState;

/**
 * effective = round((base + flat) * (1 + percent)), then overrides, clamped at zero.
 */
final class StatCalculator
{
    public function __construct(private Battle $battle)
    {
    }

    public function get(CombatantState $combatant, Stat $stat): int
    {
        $flat = 0.0;
        $percent = 0.0;
        $override = null;

        foreach ($this->modifiers($combatant) as $modifier) {
            if ($modifier->stat !== $stat) {
                continue;
            }

            match ($modifier->type) {
                ModifierType::Flat => $flat += $modifier->value,
                ModifierType::Percent => $percent += $modifier->value,
                ModifierType::Override => $override = $modifier->value,
            };
        }

        if ($override !== null) {
            return max(0, (int) round($override));
        }

        $value = ($combatant->baseStat($stat) + $flat) * (1 + $percent);

        return max(0, (int) round($value));
    }

    public function maxHealth(CombatantState $combatant): int
    {
        return $this->get($combatant, Stat::Health);
    }

    /**
     * @return array<string, int> keyed by Stat value
     */
    public function all(CombatantState $combatant): array
    {
        $stats = [];
        foreach (Stat::cases() as $stat) {
            $stats[$stat->value] = $this->get($combatant, $stat);
        }

        return $stats;
    }

    /**
     * @return Modifier[]
     */
    private function modifiers(CombatantState $combatant): array
    {
        $modifiers = [];
        foreach ($this->battle->effects->applicableTo($combatant) as [$effect, $instance]) {
            array_push($modifiers, ...$effect->modifiers($instance, $combatant, $this->battle));
        }

        return $modifiers;
    }
}
