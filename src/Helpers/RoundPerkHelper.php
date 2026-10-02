<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\RoundPerk;

class RoundPerkHelper
{
    /**
     * Perk keys that are wired into game calculations. Adding a new key here
     * without a matching call site will create a perk that does nothing.
     *
     * @return array<string, array{label: string, unit: string, negativeBenefit: bool}>
     */
    public function getPerkTypes(): array
    {
        return [
            // Military
            'offense' => ['label' => 'Offensive power', 'unit' => '%', 'negativeBenefit' => false],
            'defense' => ['label' => 'Defensive power', 'unit' => '%', 'negativeBenefit' => false],

            // Production
            'platinum_production' => ['label' => 'Platinum production', 'unit' => '%', 'negativeBenefit' => false],
            'wartime_platinum_production' => ['label' => 'Platinum production at war', 'unit' => '%', 'negativeBenefit' => false],
            'food_production' => ['label' => 'Food production', 'unit' => '%', 'negativeBenefit' => false],
            'lumber_production' => ['label' => 'Lumber production', 'unit' => '%', 'negativeBenefit' => false],
            'mana_production' => ['label' => 'Mana production', 'unit' => '%', 'negativeBenefit' => false],
            'ore_production' => ['label' => 'Ore production', 'unit' => '%', 'negativeBenefit' => false],
            'gem_production' => ['label' => 'Gem production', 'unit' => '%', 'negativeBenefit' => false],
            'tech_production' => ['label' => 'Research point production', 'unit' => '%', 'negativeBenefit' => false],

            // Costs
            'construction_cost' => ['label' => 'Construction cost', 'unit' => '%', 'negativeBenefit' => true],
            'explore_platinum_cost' => ['label' => 'Explore platinum cost', 'unit' => '%', 'negativeBenefit' => true],

            // Improvements
            'invest_bonus' => ['label' => 'Castle investment bonus', 'unit' => '%', 'negativeBenefit' => false],

            // Population
            'max_population' => ['label' => 'Max population', 'unit' => '%', 'negativeBenefit' => false],

            // Heroes
            'hero_experience' => ['label' => 'Hero experience gains', 'unit' => '%', 'negativeBenefit' => false],

            // Buildings
            'alchemy_platinum_production_raw' => ['label' => 'Platinum per alchemy', 'unit' => '', 'negativeBenefit' => false],
            'farm_food_production_raw' => ['label' => 'Food per farm', 'unit' => '', 'negativeBenefit' => false],
            'tower_mana_production_raw' => ['label' => 'Mana per tower', 'unit' => '', 'negativeBenefit' => false],
        ];
    }

    /**
     * @return string[]
     */
    public function getAlignments(): array
    {
        return ['good', 'evil'];
    }

    public function getPerkLabel(RoundPerk $perk): string
    {
        return $this->getPerkTypes()[$perk->key]['label'] ?? $perk->key;
    }

    /**
     * Formatted value, optionally colored by whether it benefits the dominion.
     */
    public function getPerkValueHtml(RoundPerk $perk, bool $colored = true): string
    {
        $perkType = $this->getPerkTypes()[$perk->key] ?? null;

        if ($perkType === null || !is_numeric($perk->value)) {
            return e($perk->value);
        }

        $value = (float)$perk->value;
        $prefix = ($value > 0 ? '+' : '');
        $formatted = $prefix . e($perk->value + 0) . $perkType['unit'];

        if (!$colored) {
            return $formatted;
        }

        $isBeneficial = (($value < 0) === $perkType['negativeBenefit']);
        $class = ($isBeneficial ? 'text-green' : 'text-red');

        return sprintf('<span class="%s">%s</span>', $class, $formatted);
    }

    /**
     * Conditions relative to the current day, e.g. "All races", "Evil races", "Ending Day 15", "Expired Day 15", "Active Day 30".
     *
     * @return string[]
     */
    public function getPerkConditionsForDay(RoundPerk $perk, int $day): array
    {
        $conditions = [];

        if ($perk->alignment !== null) {
            $conditions[] = ucfirst($perk->alignment) . ' races';
        } else {
            $conditions[] = 'All races';
        }

        if ($perk->until_day !== null && $day > $perk->until_day) {
            $conditions[] = "Expired Day {$perk->until_day}";
        } elseif ($perk->from_day !== null && $day < $perk->from_day && $perk->until_day !== null) {
            $conditions[] = "Active Day {$perk->from_day}-{$perk->until_day}";
        } elseif ($perk->from_day !== null && $day < $perk->from_day) {
            $conditions[] = "Active Day {$perk->from_day}";
        } elseif ($perk->until_day !== null) {
            $conditions[] = "Ending Day {$perk->until_day}";
        }

        return $conditions;
    }

    /**
     * Human-readable conditions, e.g. "Evil races", "Days 3-10".
     *
     * @return string[]
     */
    public function getPerkConditions(RoundPerk $perk): array
    {
        $conditions = [];

        if ($perk->alignment !== null) {
            $conditions[] = ucfirst($perk->alignment) . ' races';
        }

        if ($perk->from_day !== null && $perk->until_day !== null) {
            $conditions[] = "Days {$perk->from_day}-{$perk->until_day}";
        } elseif ($perk->from_day !== null) {
            $conditions[] = "From day {$perk->from_day}";
        } elseif ($perk->until_day !== null) {
            $conditions[] = "Until day {$perk->until_day}";
        }

        return $conditions;
    }
}
