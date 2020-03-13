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
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'boat capacity';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'construction cost';
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
                $description = ' gem production';
                break;
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'immortal wizards';
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'castle bonuses';
                break;
            case 'invest_bonus_ore':
                $negativeBenefit = false;
                $description = 'ore investment';
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
                $description = 'Population growth';
                break;
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'rezone cost';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'spy strength';
                break;
            case 'tech_production':
                $negativeBenefit = false;
                $description = 'research point gains';
                break;
            case 'wizard_strength':
                $negativeBenefit = false;
                $description = 'wizard strength';
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
            case 'boat_capacity':
                $negativeBenefit = false;
                $description = 'Boat capacity';
                $valueType = '';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'Construction cost';
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
            case 'immortal_wizards':
                $negativeBenefit = false;
                $description = 'Immortal wizards';
                $booleanValue = true;
                break;
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'Castle bonuses';
                break;
            case 'invest_bonus_ore':
                $negativeBenefit = false;
                $description = 'Ore investment';
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
            case 'rezone_cost':
                $negativeBenefit = true;
                $description = 'Rezone cost';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength';
                break;
            case 'tech_production':
                $negativeBenefit = false;
                $description = 'Research point gains';
                break;
            case 'wizard_strength':
                $negativeBenefit = false;
                $description = 'Wizard strength';
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
}
