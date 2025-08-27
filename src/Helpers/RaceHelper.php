<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerkType;

class RaceHelper
{
    public function getPerkDescriptionHtml(RacePerkType $perkType): string
    {
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'archmage cost';
                break;
            case 'assassin_cost':
                $negativeBenefit = true;
                $description = 'assassin cost';
                break;
            case 'barracks_housing':
                $negativeBenefit = false;
                $description = 'barracks housing';
                break;
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'boat capacity';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'construction cost';
                break;
            case 'explore_platinum_cost':
                $negativeBenefit = true;
                $description = 'explore platinum cost';
                break;
            case 'defense':
                $negativeBenefit = false;
                $description = 'defensive power';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'population from barren land';
                break;
            case 'food_consumption':
                $negativeBenefit = true;
                $description = 'food consumption';
                break;
            case 'food_production':
                $negativeBenefit = false;
                $description = 'food production';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = 'gem production';
                break;
            case 'hero_bonus':
                $negativeBenefit = false;
                $description = 'hero bonus';
                break;
            case 'hero_experience':
                $negativeBenefit = false;
                $description = 'hero experience gains';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'immortal wizards';
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'castle investment bonus';
                break;
            case 'invest_bonus_gems':
                $negativeBenefit = false;
                $description = 'gem investment';
                break;
            case 'invest_bonus_ore':
                $negativeBenefit = false;
                $description = 'ore investment';
                break;
            case 'lumber_decay':
                $negativeBenefit = true;
                $description = 'lumber rot';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'lumber production';
                break;
            case 'mana_production':
                $negativeBenefit = false;
                $description = 'mana production';
                break;
            case 'max_population':
                $negativeBenefit = false;
                $description = 'max population';
                break;
            case 'offense':
                $negativeBenefit = false;
                $description = 'offensive power';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'ore production';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'platinum production';
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'population growth';
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'prestige gains';
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'rezone cost';
                break;
            case 'spy_power':
                $negativeBenefit = false;
                $description = 'spy power';
                break;
            case 'spy_power_defense':
                $negativeBenefit = false;
                $description = 'defensive spy power';
                break;
            case 'spy_power_offense':
                $negativeBenefit = false;
                $description = 'offensive spy power';
                break;
            case 'spy_strength_recovery':
                $negativeBenefit = false;
                $description = 'spy strength per hour';
                break;
            case 'tech_cost':
                $negativeBenefit = true;
                $description = 'tech cost';
                break;
            case 'tech_production':
                $negativeBenefit = false;
                $description = 'research point gains';
                break;
            case 'tech_production_invasion':
                $negativeBenefit = false;
                $description = 'research point gains from invasion';
                break;
            case 'wizard_power':
                $negativeBenefit = false;
                $description = 'wizard power';
                break;
            case 'wizard_power_defense':
                $negativeBenefit = false;
                $description = 'defensive wizard power';
                break;
            case 'wizard_power_offense':
                $negativeBenefit = false;
                $description = 'offensive wizard power';
                break;
            case 'wizard_strength_recovery':
                $negativeBenefit = false;
                $description = 'wizard strength per hour';
                break;
            default:
                return '';
        }

        if ($perkType->pivot->value < 0) {
            if ($negativeBenefit) {
                return "<span class=\"text-green\">Decreased {$description}</span>";
            } else {
                return "<span class=\"text-red\">Decreased {$description}</span>";
            }
        } else {
            if ($negativeBenefit) {
                return "<span class=\"text-red\">Increased {$description}</span>";
            } else {
                return "<span class=\"text-green\">Increased {$description}</span>";
            }
        }
    }

    public function getPerkDescriptionHtmlWithValue(RacePerkType $perkType): ?array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'Archmage cost';
                $valueType = 'p';
                break;
            case 'assassin_cost':
                $negativeBenefit = true;
                $description = 'Assassin cost';
                $valueType = 'p';
                break;
            case 'barracks_housing':
                $negativeBenefit = false;
                $description = 'Barracks housing';
                $valueType = '';
                break;
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'Boat capacity';
                $valueType = '';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction cost';
                break;
            case 'explore_platinum_cost':
                $negativeBenefit = true;
                $description = 'Explore platinum cost';
                break;
            case 'defense':
                $negativeBenefit = false;
                $description = 'Defensive power';
                break;
            case 'extra_barren_max_population':
                $negativeBenefit = false;
                $description = 'Population from barren land';
                $valueType = '';
                break;
            case 'food_consumption':
                $negativeBenefit = true;
                $description = 'Food consumption';
                break;
            case 'food_production':
                $negativeBenefit = false;
                $description = 'Food production';
                break;
            case 'gem_production':
                $negativeBenefit = false;
                $description = 'Gem production';
                break;
            case 'hero_bonus':
                $negativeBenefit = false;
                $description = 'Hero bonus';
                break;
            case 'hero_experience':
                $negativeBenefit = false;
                $description = 'Hero experience gains';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'Immortal wizards';
                $booleanValue = true;
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'Castle investment bonus';
                break;
            case 'invest_bonus_gems':
                $negativeBenefit = false;
                $description = 'Gem investment';
                break;
            case 'invest_bonus_ore':
                $negativeBenefit = false;
                $description = 'Ore investment';
                break;
            case 'lumber_decay':
                $negativeBenefit = true;
                $description = 'Lumber rot';
                break;
            case 'lumber_production':
                $negativeBenefit = false;
                $description = 'Lumber production';
                break;
            case 'mana_production':
                $negativeBenefit = false;
                $description = 'Mana production';
                break;
            case 'max_population':
                $negativeBenefit = false;
                $description = 'Max population';
                break;
            case 'offense':
                $negativeBenefit = false;
                $description = 'Offensive power';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'Ore production';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'Platinum production';
                break;
            case 'population_growth':
                $negativeBenefit = false;
                $description = 'Population growth';
                break;
            case 'prestige_gains':
                $negativeBenefit = false;
                $description = 'Prestige gains';
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'Rezone cost';
                break;
            case 'spy_power':
                $negativeBenefit = false;
                $description = 'Spy power';
                break;
            case 'spy_power_defense':
                $negativeBenefit = false;
                $description = 'Defensive spy power';
                break;
            case 'spy_power_offense':
                $negativeBenefit = false;
                $description = 'Offensive spy power';
                break;
            case 'spy_strength_recovery':
                $negativeBenefit = false;
                $description = 'Spy strength per hour';
                break;
            case 'tech_cost':
                $negativeBenefit = true;
                $description = 'Tech cost';
                break;
            case 'tech_production':
                $negativeBenefit = false;
                $description = 'Research point gains';
                break;
            case 'tech_production_invasion':
                $negativeBenefit = false;
                $description = 'Research point gains from invasion';
                break;
            case 'wizard_power':
                $negativeBenefit = false;
                $description = 'Wizard power';
                break;
            case 'wizard_power_defense':
                $negativeBenefit = false;
                $description = 'Wizard power on defense';
                break;
            case 'wizard_power_offense':
                $negativeBenefit = false;
                $description = 'Wizard power on offense';
                break;
            case 'wizard_strength_recovery':
                $negativeBenefit = false;
                $description = 'Wizard strength per hour';
                break;
            default:
                return null;
        }

        $result = ['description' => $description, 'value' => ''];
        $valueString = "{$perkType->pivot->value}{$valueType}";

        if ($perkType->pivot->value < 0) {

            if($booleanValue) {
                $valueString = 'No';
            }

            if ($negativeBenefit) {
                $result['value'] = "<span class=\"text-green\">{$valueString}</span>";
            } else {
                $result['value'] = "<span class=\"text-red\">{$valueString}</span>";
            }
        } else {
            $prefix = '+';
            if($booleanValue) {
                $valueString = 'Yes';
                $prefix = '';
            }

            if ($negativeBenefit) {
                $result['value'] = "<span class=\"text-red\">{$prefix}{$valueString}</span>";
            } else {
                $result['value'] = "<span class=\"text-green\">{$prefix}{$valueString}</span>";
            }
        }

        return $result;
    }

    public function getOverallDifficultyHtml(int $difficulty): string
    {
        switch($difficulty) {
            case 0:
                return '';
            case 1:
                return '<span class="label label-success">Beginner Friendly</span>';
            case 2:
                return '';
            case 3:
                return '<span class="label label-danger">Expert</span>';
        }
    }

    public function getDifficultyString(int $difficulty): string
    {
        switch($difficulty) {
            case 0:
                return 'Not recommended';
            case 1:
                return 'Beginner';
            case 2:
                return 'Intermediate';
            case 3:
                return 'Advanced';
        }
    }
}
