<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Tech;

class TechHelper
{
    public function getTechs()
    {
        return Tech::active()->get()->keyBy('key');
    }

    public function getTechDescription(Tech $tech, string $separator = ', '): string
    {
        $perkTypeStrings = [
            // Military related
            'defense' => '%s%% defensive power',
            'offense' => '%s%% offensive power',
            'military_cost' => '%s%% military training cost',
            'guard_tax' => '%s%% platinum tax from Royal Guard',
            'prestige_gains' => '%s%% increased prestige gains',
            'boat_capacity' => '%s boat capacity',
            'barracks_housing' => '%s barracks housing',

            // Casualties related
            'fewer_casualties' => '%s%% fewer casualties',
            'fewer_casualties_defense' => '%s%% fewer casualties on defense',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense',
            'fewer_casualties_wonders' => '%s%% fewer casualties when attacking wonders',

            // Logistics
            'construction_cost' => '%s%% construction platinum cost',
            'construction_platinum_cost' => '%s%% construction platinum cost',
            'construction_lumber_cost' => '%s%% construction lumber cost',
            'destruction_refund' => '%s%% refund when destroying buildings',
            'exchange_bonus' => '%s%% better exchange rates',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 3)',
            'explore_morale_cost' => '%s%% exploring morale drop',
            'explore_platinum_cost' => '%s%% exploring platinum cost',
            'extra_barren_max_population' => '%s population from barren land',
            'max_population' => '%s%% maximum population',
            'population_growth' => '%s%% population growth',
            'rezone_cost' => '%s%% rezoning platinum cost',
            'invest_bonus_harbor' => '%s%% bonus to harbor investment',
            'invest_bonus_towers' => '%s%% bonus to towers investment',

            // Spy related
            'enemy_assassinate_draftees_damage' => '%s%% draftees lost in assassination attempts',
            'enemy_assassinate_wizards_damage' => '%s%% wizards lost in assassination attempts',
            'spy_cost' => '%s%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_strength' => '%s%% spy power',
            'spy_strength_recovery' => '%s spy strength per hour',
            'theft_gains' => '%s%% resources gained from theft',
            'theft_losses' => '%s%% resources lost to theft',

            // Wizard related
            'cyclone_damage' => '%s%% cyclone damage',
            'enemy_fireball_damage' => '%s%% enemy fireball damage',
            'enemy_lightning_bolt_damage' => '%s%% enemy lightning bolt damage',
            'spell_cost' => '%s%% cost of spells',
            'self_spell_cost' => '%s%% cost of self spells',
            'racial_spell_cost' => '%s%% cost of racial spells',
            'wizard_cost' => '%s%% cost of wizards',
            'wizard_strength' => '%s%% wizard power',
            'wizard_strength_recovery' => '%s wizard strength per hour',

            // Resource related
            'food_consumption' => '%s%% food consumption',
            'food_production_docks' => '%s%% food production from docks',
            'food_production' => '%s%% food production',
            'boat_production' => '%s%% boat production',
            'gem_production' => '%s%% gem production',
            'lumber_production' => '%s%% lumber production',
            'mana_production' => '%s%% mana production',
            'ore_production' => '%s%% ore production',
            'platinum_production' => '%s%% platinum production',
            'food_decay' => '%s%% food decay',
            'lumber_decay' => '%s%% lumber rot',
            'mana_decay' => '%s%% mana drain',
        ];

        $perkStrings = [];
        foreach ($tech->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                if ($perkValue < 0) {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], $perkValue);
                } else {
                    $perkStrings[] = vsprintf($perkTypeStrings[$perk->key], '+' . $perkValue);
                }
            }
        }

        return implode($perkStrings, $separator);
    }

    public function getX(Tech $tech): int
    {
        $parts = explode('_', $tech->key);
        if (isset($parts[1])) {
            return 10 * $parts[1];
        }
        return 0;
    }

    public function getY(Tech $tech): int
    {
        $parts = explode('_', $tech->key);
        if (isset($parts[2])) {
            return 10 * $parts[2];
        }
        return 0;
    }

    public function getTechPerkJSON(Tech $tech): string
    {
        $techPerks = [];
        foreach ($tech->perks as $perk) {
            $techPerks[$perk->key] = $perk->pivot->value;
        }
        return htmlentities(json_encode($techPerks));
    }
}
