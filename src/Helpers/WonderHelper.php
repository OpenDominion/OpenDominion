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
            'defense' => '%+g%% defensive power',
            'offense' => '%+g%% offensive power',
            'faster_return' => 'Units return %s hours faster from battle',
            'guard_tax' => '%+g%% platinum tax from Royal Guard',
            'prestige_gains' => ' %+g%% attacking prestige gains',
            'barracks_housing' => '%s barracks housing',

            // Casualties related
            'enemy_casualties_defense' => '%+g%% defensive casualties inflicted by this realm',
            'enemy_casualties_offense' => '%+g%% offensive casualties against this realm',
            'casualties_defense' => '%+g%% defensive casualties',
            'casualties_offense' => '%+g%% offensive casualties',
            'kills_immortal' => 'Can kill all immortal units',
            'max_casualties_defense' => 'Defensive casualties inflicted by this realm cannot be reduced',
            'max_casualties_offense' => 'Offensive casualties against this realm cannot be reduced',

            // Hero related
            'hero_bonus' => 'Hero bonuses increased by %d%%',
            'hero_experience' => '%+g%% hero experience gains',

            // Logistics
            'construction_cost' => '%+g%% construction platinum cost',
            'employment' => '%+g%% employment',
            'exchange_bonus' => '%+g%% bank exchange rates',
            'explore_platinum_cost' => '%+g%% exploring platinum cost',
            'extra_barren_max_population' => '%s population from barren land',
            'invest_bonus' => '%+g%% castle investment bonus',
            'max_population' => '%+g%% maximum population',
            'rezone_cost' => '%+g%% rezoning platinum cost',

            // Spy related
            'enemy_espionage_chance' => '%d%% chance of causing hostile spy operations to fail',
            'enemy_sabotage_boats_damage' => 'Boats cannot be sabotaged',
            'spy_losses' => '%+g%% spy losses on failed operations',
            'spy_strength' => '%+g%% spy power',

            // Wizard related
            'clear_sight_accuracy' => 'Clear sights against this realm are only %d%% accurate',
            'enemy_spell_damage' => '%+g%% hostile spell damage',
            'enemy_spell_chance' => '%d%% chance of causing hostile spells to fail',
            'spell_cost' => '%+g%% cost of spells',
            'spell_duration' => '%s hour self spell duration',
            'surreal_perception' => 'Grants the effects of Surreal Perception',
            'wizard_strength' => '%+g%% wizard power',

            // Resource related
            'food_production' => '%+g%% food production',
            'gem_production' => '%+g%% gem production',
            'lumber_production' => '%+g%% lumber production',
            'mana_production' => '%+g%% mana production',
            'ore_production' => '%+g%% ore production',
            'platinum_production' => '%+g%% platinum production',
            'tech_production' => '%+g%% research point production',
            'tech_production_raw' => '%+g research point production per hour',
        ];

        $perkStrings = [];
        foreach ($wonder->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                if ($perkValue < 0) {
                    $perkStrings[] = sprintf($perkTypeStrings[$perk->key], $perkValue);
                } else {
                    $perkStrings[] = sprintf($perkTypeStrings[$perk->key], '+' . $perkValue);
                }
            }
        }

        return implode(', ', $perkStrings);
    }
}
