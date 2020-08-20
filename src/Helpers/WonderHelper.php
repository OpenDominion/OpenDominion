<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Realm;
use OpenDominion\Models\Wonder;

class WonderHelper
{
    public function getWonders()
    {
        return Wonder::all();
    }

    public function getWonderDescription(Wonder $wonder): string
    {
        $perkTypeStrings = [
            // Military related
            'defense' => '%s%% defensive power',
            'offense' => '%s%% offensive power',
            'offense_evil' => '%s%% offensive power for evil races only',
            'offense_good' => '%s%% offensive power for good races only',
            'invasion_return_time' => 'Troops return %s hours faster from battle',
            'prestige_gains' => ' %s%% attacking prestige gains',
            'temple_percentage' => 'Produces the effect of %s%% temples',

            // Casualties related
            'enemy_casualties_offense' => '%s%% offensive casualties against this realm',
            'enemy_casualties_defense' => '%s%% defensive casualties inflicted by this realm',
            'fewer_casualties_defense' => '%s%% fewer casualties on defense',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense',
            'kills_immortal' => 'Can kill spirits and the undead',

            // Logistics
            'barracks_housing' => '%s barracks housing',
            'construction_cost' => '%s%% construction platinum cost',
            'employment' => '%s%% employment',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 3)',
            'explore_platinum_cost' => '%s%% exploring platinum cost',
            'invest_bonus' => '%s%% castle bonuses',
            'max_population' => '%s%% maximum population',
            'rezone_cost' => '%s%% rezoning platinum cost',

            // Spy related
            'enemy_espionage_chance' => '%s%% chance of causing hostile spy operations to fail',
            'enemy_sabotage_boats_damage' => 'Boats cannot be sabotaged',
            'spy_cost' => '%s%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_strength' => '%s%% spy power',
            'spy_strength_recovery' => '%s spy strength per hour',

            // Wizard related
            'clear_sight_accuracy' => 'Clear sights against this realm are only %s%% accurate',
            'enemy_spell_damage' => '%s%% hostile spell damage',
            'enemy_spell_chance' => '%s%% chance of causing hostile spells to fail',
            'spell_cost' => '%s%% cost of spells',
            'wizard_cost' => '%s%% cost of wizards',
            'wizard_strength' => '%s%% wizard power',
            'wizard_strength_recovery' => '%s wizard strength per hour',

            // Resource related
            'food_production' => '%s%% food production',
            'gem_production' => '%s%% gem production',
            'lumber_production' => '%s%% lumber production',
            'mana_production' => '%s%% mana production',
            'ore_production' => '%s%% ore production',
            'platinum_production' => '%s%% platinum production',
            'tech_production' => '%s%% research point production',
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

        return implode($perkStrings, ', ');
    }
}
