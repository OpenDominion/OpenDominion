<?php

namespace OpenDominion\Helpers;

use LogicException;
use OpenDominion\Models\Race;
use OpenDominion\Models\RacePerkType;

class RaceHelper
{
    public function getRaceDescriptionHtml(Race $race): string
    {
        $descriptions = [];

        // Good races

        $descriptions['dwarf'] = <<<DWARF
<p>Defined by their majestic beards and their love for booze and labor, these descendants of Caedair Hold have come to fight for the forces of good.</p>
<p>Short and grumpy, they harbor an intense hatred towards Goblins.</p>
DWARF;

        $descriptions['firewalker'] = <<<FIREWALKER
<p>Beings of pure fire, which came into this world after a powerful sorcerer once got too greedy with their pyro experimentation projects.</p>
<p>Excellent at proliferation, these fiery beasts seem highly interested in leaving only ash in their wake.</p>
FIREWALKER;

        $descriptions['gnome'] = <<<GNOME
<p>These ingenious little people are the masters of invention and tinkering technology.</p>
<p>Although slow and expensive due their metallic augments, their powerful machinery can turn the tide of battles in the late game.</p>
GNOME;

        $descriptions['halfling'] = <<<HALFLING
<p>A cheerful and adventurous race known for their diminutive stature and furry, bare feetses. They are exceptionally stealthy due to their size rather than grace.</p>
<p>Fiercely loyal to family and friends, they will defend their homeland with surprising fortitude.</p>
HALFLING;

        $descriptions['human'] = <<<HUMAN
<p>These noble and religious Humans hail from fallen city of Brimstone Keep.</p>
<p>Proficient at everything but excelling at nothing, they are a well-balanced and self-sufficient race.</p>
HUMAN;

        $descriptions['sylvan'] = <<<SYLVAN
<p>Mythical forest-dwelling creatures, which have banded together to combat the forces of evil.</p>
<p>Their affinity for nature makes them excellent at exploration, and highly proficient spellcasters.</p>
SYLVAN;

        // Evil races

        $descriptions['dark elf'] = <<<DARKELF
<p>Dark elves are known for their aggression, deceit, and stealth. These creatures are very brutal and cruel by nature, having little regard for mercy.</p>
<p>They lurk in dark places, love the shadows, and have a natural inclination towards magic and the dark arts.</p>
DARKELF;

        $descriptions['goblin'] = <<<GOBLIN
<p>What they lack in intelligence, they make up for in sheer numbers. They love slaughtering other living things as much as they love shiny gems.</p>
<p>Short, cunning, and gnarling, they hate anything that smells like Dwarf.</p>
GOBLIN;

        $descriptions['icekin'] = <<<ICEKIN
<p>These icy creatures come from the cold mountains, up in the far north.</p>
<p>Icekin are a slow race that make for excellent explorers and late-game attacking.</p>
ICEKIN;

        $descriptions['lizardfolk'] = <<<LIZARDFOLK
<p>These amphibious creatures hail from the depths of the seas, having remained mostly hidden for decades before resurfacing and joining the war.</p>
<p>Lizardfolk are highly proficient at both performing and countering espionage operations, and make for excellent incursions on unsuspecting targets.</p>
LIZARDFOLK;

        $descriptions['orc'] = <<<ORC
<p>Known for their barbaric behavior and lack of intelligence, these warmongering creatures have an insatiable hunger for destruction.</p>
<p>Orcs are proud warriors with a strong sense of honor. As formidable as they are, their direct approach to warfare is weak against a fortified position.</p>
ORC;

        $descriptions['nomad'] = <<<NOMAD
<p>Descendants of Humans, these folk have been exiled from the kingdom long ago and went their own way.</p>
<p>Acclimated to the desert life, these traveling Nomads teamed up with the evil races out of spite towards the Humans and their allies.</p>
NOMAD;

        $descriptions['troll'] = <<<TROLL
<p>Clans of dumb brutes, hulking in stature, originally thought to have evolved from the elves long ago.</p>
<p>Trolls are excellent at smashing others, especially things smaller than themselves. <em>[Urg smash puny gnome!]</em></p>
TROLL;

        $key = strtolower($race->name);

        if (!isset($descriptions[$key])) {
            throw new LogicException("Racial description for {$key} needs implementing");
        }

        return $descriptions[$key];
    }

    public function getPerkDescriptionHtml(RacePerkType $perkType): string
    {
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'archmage cost';
                break;
            case 'construction_cost':
                $negativeBenefit = true;
                $description = 'construction cost';
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
            case 'invest_bonus':
                $negativeBenefit = false;
                $description = 'castle bonuses';
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
                $description = 'offense';
                break;
            case 'ore_production':
                $negativeBenefit = false;
                $description = 'ore production';
                break;
            case 'platinum_production':
                $negativeBenefit = false;
                $description = 'platinum production';
                break;
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'spy strength';
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
}
