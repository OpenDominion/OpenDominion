<?php

namespace OpenDominion\Helpers;

class RankingsHelper
{
    public function getRankings()
    {
        /*
        Title     Meaning
        the Mighty     Strongest Good (Steadfast)   ra-flat-hammer
        the Noble     Largest Good                  ra-large-hammer
        the Strong     Strongest Evil               ra-blade-bite
        the Feared     Largest Evil                 ra-death-skull
        the Bold     Strongest Monarch              ra-crown
        the Just     Largest Monarch                ra-crowned-heart
        */

        return collect([
            [
                'name' => 'The Largest Dominions',
                'key' => 'largest-dominions',
                'stat' => 'land',
                'stat_label' => 'Land',
                'title' => 'the Powerful',
                'title_icon' => 'ra-helmet',
            ],
            [
                'name' => 'The Strongest Dominions',
                'key' => 'strongest-dominions',
                'stat' => 'networth',
                'stat_label' => 'Networth',
                'title' => 'the Destroyer',
                'title_icon' => 'ra-helmet',
            ],
            [
                'name' => 'Largest Attacking Dominions',
                'key' => 'total-land-conquered',
                'stat' => 'stat_total_land_conquered',
                'stat_label' => 'Land Conquered',
                'title' => 'the Mighty',
                'title_icon' => 'ra-battered-axe',
            ],
            [
                'name' => 'Largest Exploring Dominions',
                'key' => 'total-land-explored',
                'stat' => 'stat_total_land_explored',
                'stat_label' => 'Land Explored',
                'title' => 'the Adventurous',
                'title_icon' => 'ra-fedora',
            ],
            [
                'name' => 'Most Victorious Dominions',
                'key' => 'attacking-success',
                'stat' => 'stat_attacking_success',
                'stat_label' => 'Successful Attacks',
                'title' => 'the Courageous',
                'title_icon' => 'ra-cracked-helm',
            ],
            [
                'name' => 'Most Prestigious Dominions',
                'key' => 'prestige',
                'stat' => 'prestige',
                'stat_label' => 'Prestige',
                'title' => 'the Renowned',
                'title_icon' => 'ra-all-for-one',
            ],
            [
                'name' => 'Most Successful Spies',
                'key' => 'espionage-success',
                'stat' => 'stat_espionage_success',
                'stat_label' => 'Successful Spy Ops',
                'title' => 'the Thief',
                'title_icon' => 'ra-hood',
            ],
            [
                'name' => 'Most Prestigious Spies',
                'key' => 'spy-prestige',
                'stat' => 'stat_spy_prestige',
                'stat_label' => 'Spy Prestige',
                'title' => 'the Sneaky',
                'title_icon' => 'ra-hood',
            ],
            [
                'name' => 'Most Spies Executed',
                'key' => 'spies-executed',
                'stat' => 'stat_spies_executed',
                'stat_label' => 'Spies Executed',
                'title' => 'the Executioner',
                'title_icon' => 'ra-guillotine',
            ],
            [
                'name' => 'Top Saboteurs',
                'key' => 'saboteurs',
                'stat' => 'stat_sabotage_boats_damage',
                'stat_label' => 'Boats Sabotaged',
                'title' => 'the Sinker',
                'title_icon' => 'ra-bomb-explosion',
            ],
            [
                'name' => 'Top Magical Assassins',
                'key' => 'magical-assassins',
                'stat' => 'stat_assassinate_wizards_damage',
                'stat_label' => 'Wizards Assassinated',
                'title' => 'the Wizard Bane',
                'title_icon' => 'ra-decapitation',
            ],
            [
                'name' => 'Top Military Assassins',
                'key' => 'military-assassins',
                'stat' => 'stat_assassinate_draftees_damage',
                'stat_label' => 'Draftees Assassinated',
                'title' => 'the Assassin',
                'title_icon' => 'ra-plain-dagger',
            ],
            [
                'name' => 'Top Snare Setters',
                'key' => 'snare-setters',
                'stat' => 'stat_magic_snare_damage',
                'stat_label' => 'Snare Impact',
                'title' => 'the Trickster',
                'title_icon' => 'ra-fire-ring', // ra-burning-eye
            ],
            [
                'name' => 'Top Platinum Thieves',
                'key' => 'platinum-thieves',
                'stat' => 'stat_total_platinum_stolen',
                'stat_label' => 'Platinum Stolen',
                'title' => 'the Devious',
                'title_icon' => 'ra-gold-bar',
            ],
            [
                'name' => 'Top Lumber Thieves',
                'key' => 'lumber-thieves',
                'stat' => 'stat_total_lumber_stolen',
                'stat_label' => 'Lumber Stolen',
                'title' => 'the Sly',
                'title_icon' => 'ra-wooden-sign',
            ],
            [
                'name' => 'Top Gem Thieves',
                'key' => 'gem-thieves',
                'stat' => 'stat_total_gems_stolen',
                'stat_label' => 'Gems Stolen',
                'title' => 'the Cutpurse ',
                'title_icon' => 'ra-diamond',
            ],
            [
                'name' => 'Top Ore Thieves',
                'key' => 'ore-thieves',
                'stat' => 'stat_total_ore_stolen',
                'stat_label' => 'Ore Stolen',
                'title' => 'the Crafty',
                'title_icon' => 'ra-mine-wagon',
            ],
            [
                'name' => 'Top Food Thieves',
                'key' => 'food-thieves',
                'stat' => 'stat_total_food_stolen',
                'stat_label' => 'Food Stolen',
                'title' => 'the Hungry',
                'title_icon' => 'ra-carrot',
            ],
            [
                'name' => 'Top Mana Thieves',
                'key' => 'mana-thieves',
                'stat' => 'stat_total_mana_stolen',
                'stat_label' => 'Mana Stolen',
                'title' => 'the Shifty',
                'title_icon' => 'ra-crystal-ball',
            ],
            [
                'name' => 'Most Successful Wizards',
                'key' => 'spell-success',
                'stat' => 'stat_spell_success',
                'stat_label' => 'Successful Wizard Ops',
                'title' => 'the Magical',
                'title_icon' => 'ra-crystal-wand',
            ],
            [
                'name' => 'Most Prestigious Wizards',
                'key' => 'wizard-prestige',
                'stat' => 'stat_wizard_prestige',
                'stat_label' => 'Wizard Prestige',
                'title' => 'the Archmage',
                'title_icon' => 'ra-crystal-wand',
            ],
            [
                'name' => 'Most Wizards Executed',
                'key' => 'wizards-executed',
                'stat' => 'stat_wizards_executed',
                'stat_label' => 'Wizards Executed',
                'title' => '',
                'title_icon' => '',
            ],
            [
                'name' => 'Masters of Fire',
                'key' => 'masters-of-fire',
                'stat' => 'stat_fireball_damage',
                'stat_label' => 'Peasants Killed',
                'title' => 'the Master of Fire',
                'title_icon' => 'ra-fire',
            ],
            [
                'name' => 'Masters of Lightning',
                'key' => 'masters-of-lightning',
                'stat' => 'stat_lightning_bolt_damage',
                'stat_label' => 'Lightning Damage',
                'title' => 'the Master of Lightning',
                'title_icon' => 'ra-lightning-trio',
            ],
            [
                'name' => 'Masters of Plague',
                'key' => 'masters-of-plague',
                'stat' => 'stat_plague_hours',
                'stat_label' => 'Plague Hours',
                'title' => 'the Master of Plague',
                'title_icon' => 'ra-vial', // ra-acid
            ],
            [
                'name' => 'Masters of Swarm',
                'key' => 'masters-of-swarm',
                'stat' => 'stat_insect_swarm_hours',
                'stat_label' => 'Swarm Hours',
                'title' => 'the Master of Swarm',
                'title_icon' => 'ra-insect-jaws',
            ],
            [
                'name' => 'Masters of Water',
                'key' => 'masters-of-water',
                'stat' => 'stat_great_flood_hours',
                'stat_label' => 'Great Flood Hours',
                'title' => 'the Master of Water',
                'title_icon' => '', // ra-ocean-emblem
            ],
            [
                'name' => 'Masters of Earth',
                'key' => 'masters-of-earth',
                'stat' => 'stat_earthquake_hours',
                'stat_label' => 'Earthquake Hours',
                'title' => 'the Master of Earth',
                'title_icon' => '',
            ],
            [
                'name' => 'Top Spy Disbanders',
                'key' => 'spy-disbanders',
                'stat' => 'stat_disband_spies_damage',
                'stat_label' => 'Spies Disbanded',
                'title' => 'the Spy Bane',
                'title_icon' => 'ra-player-pain',
            ],
        ])->keyBy('key')->toArray();
    }

    function getFirstRanking(array $keys): array
    {
        foreach ($this->getRankings() as $ranking) {
            if (in_array($ranking['key'], $keys)) {
                return $ranking;
            }
        }

        return [];
    }
}
