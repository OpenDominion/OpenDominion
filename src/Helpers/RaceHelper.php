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

        // new
        $descriptions['dwarf'] = <<<DWARF
<p>Dwarves are a short and ill-tempered race well-known for their majestic beards, their love of ale and tireless labour. Their spirited chants and songs echo long into the night as they hollow out entire mountains for their ore.</p>
<p>Dwarven mines are the most productive in the lands, producing a steady flow of ore used to fortify their great cities, and craft legendary Dwarven armour for their military forces.</p>
DWARF;

        // new
        $descriptions['firewalker'] = <<<FIREWALKER
<p>The first Firewalker erupted into existence from the smouldering ashes of a greedy scientist that had sought to enrich himself with forbidden alchemical practices, combining chemistry and pyromancy to forge platinum from the earth itself.</p>
<p>Turning entire networks of caverns into vast furnaces where they live as one flame, Firewalker alchemies enhanced by pyromantic magics are the most productive in the lands, and their populations spread like wildfire wherever they ignite.</p>
FIREWALKER;

        $descriptions['gnome'] = <<<GNOME
<p>These ingenious little people are the masters of invention and tinkering technology.</p>
<p>Although slow and expensive due their metallic augments, their powerful machinery can turn the tide of battles in the late game.</p>
GNOME;

        $descriptions['halfling'] = <<<HALFLING
<p>A cheerful and adventurous race known for their diminutive stature and furry, bare feetses. They are exceptionally stealthy due to their size rather than grace.</p>
<p>Fiercely loyal to family and friends, they will defend their homeland with surprising fortitude.</p>
HALFLING;

        // new
        $descriptions['human'] = <<<HUMAN
<p>Among the youngest of the races, the Human empire rose swiftly and against the odds. Humans proved to be not only capable warriors, but also skilled smiths, clever engineers and above all, adaptable. Their homeland destroyed by the forces of Evil decades ago, Humans seek to rebuild and avenge their fallen brothers.</p>
<p>Humans are generally proficient in everything they set their mind to, though they are masters of no single discipline.</p>
HUMAN;

        // new
        $descriptions['merfolk'] = <<<MERFOLK
<p>An aquatic race that lives in beautiful coral reefs where food is plentiful, Merfolk are the benevolent guardians of the great oceans.</p>
<p>Though typically peaceful, Merfolk are legendary for their wrath when angered, summoning terrors of the deep to destroy entire naval fleets with the thrashing tentacles of ravenous krakens. The chilling and alluring call of the psiren might be the last thing you ever hear... before you're snatched from your ship and dragged down to the bottom of the cold, dark sea.</p>
MERFOLK;

        $descriptions['spirit'] = <<<SPIRIT
<p>These kind spirits long for a quiet and peaceful world.</p>
<p>Some of the lost souls of fallen enemies, will join their ranks in search of this goal.</p>
SPIRIT;

        $descriptions['sylvan'] = <<<SYLVAN
<p>Mythical forest-dwelling creatures, which have banded together to combat the forces of evil.</p>
<p>Their affinity for nature makes them excellent at exploration, and highly proficient spellcasters.</p>
SYLVAN;

        // new
        $descriptions['wood elf'] = <<<WOODELF
<p>Graceful, slender and eerily beautiful, the Wood Elves are among the eldest of the races and keenly attuned with the natural world, seeking to protect their forests from the forces of evil.</p>
<p>Though peaceful by nature, Wood Elves are a versatile race, proficient in combat with their deadly archers and magically gifted druids that draw power from the very forest itself, and backed up by powerful wizards and skilled spies that excel at covert ops.</p>
WOODELF;

        // Evil races

        // new
        $descriptions['dark elf'] = <<<DARKELF
<p>With ashen skin, inky-black eyes and bat-like features, Dark Elves are the cave-dwelling distant cousins of the majestic Wood Elves. Corrupted long ago by the whispered promises of power from fallen demons, the Dark Elves are a cruel species who thrive on torment... be it the torment of their enemies, or even their own kin.</p>
<p>Dark Elves have black magic coursing through their veins. Capable of calling down a rain of fire and lightning upon their enemies, they are a terrifying force to reckon with - and that's before they even set foot on the battlefield.</p>
DARKELF;

        // new
        $descriptions['goblin'] = <<<GOBLIN
<p>Small in stature but great in number, Goblins are a vicious and single-minded breed that prefer to take down their enemies with overwhelming numbers - and then steal all the shinies.</p>
<p>Goblin populations can grow quickly out of control if left unchecked, and these short, green and ugly wretches have been known to completely ransack well-fortified castles in their relentless pursuit of gems, gems and more gems.</p>
GOBLIN;

        // new
        $descriptions['icekin'] = <<<ICEKIN
<p>Slow, lumbering elementals of frost and stone, Icekin emerged from the snow-capped mountains as a counterweight to unnatural the pyromancy experiments that created the Firewalkers.</p>
<p>Their creeping cold expands ever outwards, insistent, transforming the lands around them with a white permafrost and hijacking the weather with seemingly never-ending blizzards. Icekin can become an immense military threat once they become well fortified.</p>
ICEKIN;

        $descriptions['lizardfolk'] = <<<LIZARDFOLK
<p>These amphibious creatures hail from the depths of the seas, having remained mostly hidden for decades before resurfacing and joining the war.</p>
<p>Lizardfolk are highly proficient at both performing and countering espionage operations, and make for excellent incursions on unsuspecting targets.</p>
LIZARDFOLK;

        // new
        $descriptions['lycanthrope'] = <<<LYCANTHROPE
<p>Once thought to be an ancient curse that transformed men into wolves under the light of a full moon, little is understood about the Lycanthropic affliction. But one thing's for certain: once bitten, you'll never be the same again.</p>
<p>Capable of agonising transformations into half-beast monsters, Lycanthropes are a hardy and fast-growing race, turning their enemies into werewolves and regenerating non-lethal wounds mid-combat.</p>
LYCANTHROPE;

        $descriptions['nomad'] = <<<NOMAD
<p>Descendants of Humans, these folk have been exiled from the kingdom long ago and went their own way.</p>
<p>Acclimated to the desert life, these traveling Nomads teamed up with the evil races out of spite towards the Humans and their allies.</p>
NOMAD;

        $descriptions['nox'] = <<<NOX
<p>The children of the night lurk in the shadows, striking terror in even the most powerful of rulers.</p>
<p>Nox can be found in the deepest darkness where even Dark Elves won't dare to trespass.</p>
NOX;

        // new
        $descriptions['troll'] = <<<TROLL
<p>Hard to kill, and even harder to look at without screaming, Trolls are the hideously ugly but tremendously powerful genetic dead end of an ancient elven race.</p>
<p>Trolls are notoriously bloodthirsty. What they lack in subtlety they more than make up for in gratuitous violence. It is not uncommon to see fully-armoured soldiers being punted up sixty feet into the air during Troll invasions. <em>[Urg smash!]</em></p>
TROLL;

        $descriptions['undead'] = <<<UNDEAD
<p>An unending horde of beings that have overcome death, the undead have an insatiable desire to destroy all living creatures.</p>
<p>They are always on the offensive, increasing their number by reanimating fallen enemies.</p>
UNDEAD;

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

    public function getPerkDescriptionHtmlWithValue(RacePerkType $perkType): array
    {
        $valueType = '%';
        $booleanValue = false;
        switch($perkType->key) {
            case 'archmage_cost':
                $negativeBenefit = true;
                $description = 'Archmage cost';
                $valueType = 'p';
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
            case 'spy_strength':
                $negativeBenefit = false;
                $description = 'Spy strength';
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
