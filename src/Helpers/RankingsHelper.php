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
        the Devious     Top Platinum Thief          ra-gold-bar
        the Sly     Top Lumber Thief                ra-wooden-sign
        the Cutpurse     Top Gem Thief              ra-diamond
        the Crafty     Top Ore Thief                ra-mine-wagon
        the Hungry     Top Food Thief               ra-carrot
        the Shifty     Top Mana Thief               ra-crystal-ball
        */

        return collect([
            [
                'name' => 'The Largest Dominions',
                'key' => 'largest-dominions',
                'stat' => 'land',
                'title' => 'the Powerful',
                'title_icon' => 'ra-trophy',
            ],
            [
                'name' => 'The Strongest Dominions',
                'key' => 'strongest-dominions',
                'stat' => 'networth',
                'title' => 'the Destroyer',
                'title_icon' => 'ra-helmet',
            ],
            [
                'name' => 'Largest Attacking Dominions',
                'key' => 'total-land-conquered',
                'stat' => 'stat_total_land_conquered',
                'title' => 'the Mighty',
                'title_icon' => 'ra-battered-axe',
            ],
            [
                'name' => 'Largest Exploring Dominions',
                'key' => 'total-land-explored',
                'stat' => 'stat_total_land_conquered',
                'title' => 'the Adventurous',
                'title_icon' => 'ra-fedora',
            ],
            [
                'name' => 'Most Victorious Dominions',
                'key' => 'attacking-success',
                'stat' => 'stat_attacking_success',
                'title' => 'the Courageous',
                'title_icon' => 'ra-cracked-helm',
            ],
            [
                'name' => 'Most Prestigious Dominions',
                'key' => 'prestige',
                'stat' => 'prestige',
                'title' => 'the Renowned',
                'title_icon' => 'ra-all-for-one',
            ],
            [
                'name' => 'Most Successful Spies',
                'key' => 'espionage-success',
                'stat' => 'stat_espionage_success',
                'title' => 'the Thief',
                'title_icon' => 'ra-hood',
            ],
            [
                'name' => 'Most Prestigious Spies',
                'key' => 'spy-prestige',
                'stat' => 'stat_spy_prestige',
                'title' => 'the Sneaky',
                'title_icon' => 'ra-hood',
            ],
            [
                'name' => 'Top Saboteurs',
                'key' => 'top-saboteurs',
                'stat' => 'stat_sabotage_boats_damage',
                'title' => 'the Sinker',
                'title_icon' => 'ra-bomb-explosion',
            ],
            [
                'name' => 'Top Magical Assassins',
                'key' => 'top-magical-assassins',
                'stat' => 'stat_assassinate_wizards_damage',
                'title' => 'the Wizard Bane',
                'title_icon' => 'ra-decapitation',
            ],
            [
                'name' => 'Top Military Assassins',
                'key' => 'top-military-assassins',
                'stat' => 'stat_assassinate_draftees_damage',
                'title' => 'the Assassin',
                'title_icon' => 'ra-plain-dagger',
            ],
            [
                'name' => 'Top Snare Setters',
                'key' => 'top-snare-setters',
                'stat' => 'stat_magic_snare_damage',
                'title' => 'the Trickster',
                'title_icon' => 'ra-fire-ring', // ra-burning-eye
            ],
            [
                'name' => 'Most Successful Wizards',
                'key' => 'spell-success',
                'stat' => 'stat_spell_success',
                'title' => 'the Magical',
                'title_icon' => 'ra-crystal-wand',
            ],
            [
                'name' => 'Most Prestigious Wizards',
                'key' => 'wizard-prestige',
                'stat' => 'stat_wizard_prestige',
                'title' => 'the Archmage',
                'title_icon' => 'ra-crystal-wand',
            ],
            [
                'name' => 'Masters of Fire',
                'key' => 'masters-of-fire',
                'stat' => 'stat_fireball_damage',
                'title' => 'the Master of Fire',
                'title_icon' => 'ra-fire',
            ],
            [
                'name' => 'Masters of Lightning',
                'key' => 'masters-of-lightning',
                'stat' => 'stat_lightning_bolt_damage',
                'title' => 'the Master of Lightning',
                'title_icon' => 'ra-lightning-trio',
            ],
            [
                'name' => 'Masters of Plague',
                'key' => 'masters-of-plague',
                'stat' => 'stat_plague_hours',
                'title' => 'the Master of Plague',
                'title_icon' => 'ra-vial', // ra-acid
            ],
            [
                'name' => 'Masters of Swarm',
                'key' => 'masters-of-swarm',
                'stat' => 'stat_insect_swarm_hours',
                'title' => 'the Master of Swarm',
                'title_icon' => 'ra-insect-jaws',
            ],
            [
                'name' => 'Masters of Water',
                'key' => 'masters-of-water',
                'stat' => 'stat_great_flood_hours',
                'title' => '',
                'title_icon' => '', // ra-ocean-emblem
            ],
            [
                'name' => 'Masters of Earth',
                'key' => 'masters-of-earth',
                'stat' => 'stat_earthquake_hours',
                'title' => '',
                'title_icon' => '',
            ],
            [
                'name' => 'Top Spy Disbanders',
                'key' => 'top-spy-disbanders',
                'stat' => 'stat_disband_spies_damage',
                'title' => 'the Spy Bane',
                'title_icon' => 'ra-player-pain',
            ],
        ])->keyBy('key');
    }
}
