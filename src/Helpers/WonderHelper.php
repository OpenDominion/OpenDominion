<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Realm;
use OpenDominion\Models\Wonder;

class WonderHelper
{
    public function getWonders()
    {
        return Wonder::active()->orderBy('name')->get();
    }

    public function getWonderDescription(Wonder $wonder): string
    {
        $perkTypeStrings = [
            // Military related
            'defense' => '%+d%% defensive power',
            'offense' => '%+d%% offensive power',
            'faster_return' => 'Units return %s hours faster from battle',
            'guard_tax' => '%+d%% platinum tax from Royal Guard',
            'prestige_gains' => ' %+d%% attacking prestige gains',
            'barracks_housing' => '%s barracks housing',

            // Casualties related
            'enemy_casualties_defense' => '%+d%% defensive casualties inflicted by this realm',
            'enemy_casualties_offense' => '%+d%% offensive casualties against this realm',
            'casualties_defense' => '%d%% defensive casualties',
            'casualties_offense' => '%d%% offensive casualties',
            'kills_immortal' => 'Can kill all immortal units',
            'max_casualties_defense' => 'Defensive casualties inflicted by this realm cannot be reduced',
            'max_casualties_offense' => 'Offensive casualties against this realm cannot be reduced',

            // Logistics
            'construction_cost' => '%+d%% construction platinum cost',
            'employment' => '%+d%% employment',
            'exchange_bonus' => '%+d%% bank exchange rates',
            'explore_platinum_cost' => '%+d%% exploring platinum cost',
            'extra_barren_max_population' => '%s population from barren land',
            'invest_bonus' => '%+d%% castle investment bonus',
            'max_population' => '%+d%% maximum population',
            'rezone_cost' => '%+d%% rezoning platinum cost',

            // Spy related
            'enemy_espionage_chance' => '%d%% chance of causing hostile spy operations to fail',
            'enemy_sabotage_boats_damage' => 'Boats cannot be sabotaged',
            'spy_losses' => '%+d%% spy losses on failed operations',
            'spy_strength' => '%+d%% spy power',

            // Wizard related
            'clear_sight_accuracy' => 'Clear sights against this realm are only %d%% accurate',
            'enemy_spell_damage' => '%+d%% hostile spell damage',
            'enemy_spell_chance' => '%d%% chance of causing hostile spells to fail',
            'spell_cost' => '%+d%% cost of spells',
            'spell_duration' => '%s hour self spell duration',
            'surreal_perception' => 'Grants the effects of Surreal Perception',
            'wizard_strength' => '%+d%% wizard power',

            // Resource related
            'food_production' => '%+d%% food production',
            'gem_production' => '%+d%% gem production',
            'lumber_production' => '%+d%% lumber production',
            'mana_production' => '%+d%% mana production',
            'ore_production' => '%+d%% ore production',
            'platinum_production' => '%+d%% platinum production',
            'tech_production' => '%+d%% research point production',
            'tech_production_raw' => '%s research point production per hour',
        ];

        $perkStrings = [];
        foreach ($wonder->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                if ($perkValue < 0) {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], $perkValue);
                } else {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], '+' . $perkValue);
                }
            }
        }

        return implode(', ', $perkStrings);
    }
}
