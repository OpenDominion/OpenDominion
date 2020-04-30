<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Tech;

class TechHelper
{
    public function getTechs()
    {
        return Tech::all()->keyBy('key');
    }

    public function getTechDescription(Tech $tech): string
    {
        $perkTypeStrings = [
            // Military related
            'defense' => '%s%% defensive power',
            'offense' => '%s%% offensive power',
            'military_cost' => '%s%% military training cost',

            // Casualties related
            'fewer_casualties_defense' => '%s%% fewer casualties on defense',
            'fewer_casualties_offense' => '%s%% fewer casualties on offense',

            // Logistics
            'construction_cost' => '%s%% construction platinum cost',
            'explore_draftee_cost' => '%s draftee per acre explore cost (min 3)',
            'explore_platinum_cost' => '%s%% exploring platinum cost',
            'max_population' => '%s%% maximum population',
            'rezone_cost' => '%s%% rezoning platinum cost',

            // Spy related
            'spy_cost' => '%s%% cost of spies',
            'spy_losses' => '%s%% spy losses on failed operations',
            'spy_strength' => '%s%% spy power',
            'spy_strength_recovery' => '%s spy strength per hour',

            // Wizard related
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

        return implode($perkStrings, ', ');
    }
}
