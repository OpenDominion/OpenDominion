<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\Tech;

class TechHelper
{
    public const CURRENT_VERSION = 2;

    public function getTechs(int $version = self::CURRENT_VERSION)
    {
        return Tech::with('perks')->where('version', $version)->get()->keyBy('key');
    }

    public function getTechPerkStrings()
    {
        return [
            // Military related
            'defense' => '%+g%% defensive power',
            'offense' => '%+g%% offensive power',
            'military_cost' => '%+g%% military training cost',
            'guard_tax' => '%+g%% platinum tax from Royal Guard',
            'prestige_gains' => '%+g%% increased prestige gains',
            'boat_capacity' => '%+g boat capacity',
            'barracks_housing' => '%+g barracks housing',
            'raid_attack_damage' => '%+g%% attack damage in raids',

            // Casualties related
            'casualties' => '%+g%% casualties',
            'casualties_defense' => '%+g%% defensive casualties',
            'casualties_offense' => '%+g%% offensive casualties',
            'casualties_wonders' => '%+g%% casualties when attacking wonders',

            // Logistics
            'construction_cost' => '%+g%% construction platinum cost',
            'construction_platinum_cost' => '%+g%% construction platinum cost',
            'construction_lumber_cost' => '%+g%% construction lumber cost',
            'destruction_discount' => '%g%% of destroyed buildings can be rebuilt at a discount (excluding Nomad)',
            'destruction_refund' => '%+g%% refund when destroying buildings',
            'exchange_bonus' => '%+g%% better exchange rates',
            'explore_draftee_cost' => '%+g draftee per acre explore cost (min 6)',
            'explore_platinum_cost' => '%g%% exploring platinum cost (halved for Firewalker, Goblin, Lycanthrope, Vampire)',
            'extra_barren_max_population' => '%+g population from barren land',
            'max_population' => '%+g%% maximum population',
            'population_growth' => '%+g%% population growth',
            'rezone_cost' => '%+g%% rezoning platinum cost',
            'invest_bonus_harbor' => '%+g%% bonus to harbor investment',
            'invest_bonus_spires' => '%+g%% bonus to spires investment',

            // Spy related
            'assassin_cost' => '%+g%% cost of assassins',
            'enemy_assassinate_draftees_damage' => '%+g%% draftees lost in assassination attempts',
            'enemy_assassinate_wizards_damage' => '%+g%% wizards lost in assassination attempts',
            'spy_cost' => '%+g%% cost of spies',
            'spy_losses' => '%+g%% spy losses on failed operations',
            'spy_power' => '%+g%% spy power',
            'spy_power_defense' => '%+g%% defensive spy power',
            'spy_strength_recovery' => '%+g spy strength per hour',
            'theft_gains' => '%+g%% resources gained from theft',
            'theft_losses' => '%+g%% resources lost to theft',
            'fools_gold_cost' => '%+g%% Fool\'s Gold mana cost',
            'improved_fools_gold' => 'Fool\'s Gold now protects ore/lumber/mana',

            // Resource related
            'food_consumption' => '%+g%% food consumption',
            'food_production' => '%+g%% food production',
            'food_production_docks' => '%+g%% food production from docks',
            'food_production_prestige' => '%+g%% food production from prestige',
            'boat_production' => '%+g%% boat production',
            'gem_production' => '%+g%% gem production',
            'lumber_production' => '%+g%% lumber production',
            'mana_production' => '%+g%% mana production',
            'mana_production_raw' => '%+g mana production per tower',
            'wartime_mana_production_raw' => '%+g mana production per tower for each war relation (max 2)',
            'ore_production' => '%+g%% ore production',
            'platinum_production' => '%+g%% platinum production',
            'food_decay' => '%+g%% food decay',
            'lumber_decay' => '%+g%% lumber rot',
            'mana_decay' => '%+g%% mana drain',

            // Wizard related
            'archmage_cost' => '%+g%% cost of archmages',
            'enemy_disband_spies_damage' => '%+g%% enemy disband spies damage',
            'enemy_fireball_damage' => '%+g%% enemy fireball damage',
            'enemy_lightning_bolt_damage' => '%+g%% enemy lightning bolt damage',
            'enemy_spell_duration' => '%+g black op spell duration',
            'enemy_burning_duration' => '%+g Burning duration',
            'spell_cost' => '%+g%% cost of spells',
            'self_spell_cost' => '%+g%% cost of self spells',
            'racial_spell_cost' => '%+g%% cost of racial spells',
            'wizard_cost' => '%+g%% cost of wizards',
            'wizard_power' => '%+g%% wizard power',
            'wizard_strength_recovery' => '%+g wizard strength per hour',
            'wonder_damage' => '%+g%% wonder damage',
        ];
    }

    public function getTechDescription(Tech $tech, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getTechPerkStrings();

        $perkStrings = [];
        foreach ($tech->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->pivot->value;
                $perkStrings[] = sprintf($perkTypeStrings[$perk->key], $perkValue);
            }
        }

        return implode($separator, $perkStrings);
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
