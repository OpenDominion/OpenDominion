<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;

class SpellHelper
{
    public function getSpellInfo(string $spellKey, Race $race): array
    {
        return $this->getSpells($race)->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->first();
    }

    public function isSelfSpell(string $spellKey, Race $race): bool
    {
        return $this->getSelfSpells($race)->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->isNotEmpty();
    }

    public function isOffensiveSpell(string $spellKey): bool
    {
        return $this->getOffensiveSpells()->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->isNotEmpty();
    }

    public function isInfoOpSpell(string $spellKey): bool
    {
        return $this->getInfoOpSpells()->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->isNotEmpty();
    }

    public function isBlackOpSpell(string $spellKey): bool
    {
        return $this->getBlackOpSpells()->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->isNotEmpty();
    }

    public function isWarSpell(string $spellKey): bool
    {
        return $this->getWarSpells()->filter(function ($spell) use ($spellKey) {
            return ($spell['key'] === $spellKey);
        })->isNotEmpty();
    }

    public function getSpells(Race $race): Collection
    {
        return $this->getSelfSpells($race)
            ->merge($this->getOffensiveSpells());
    }

    public function getSelfSpells(Race $race): Collection
    {
        $raceName = $race->name;

        $racialSpell = $this->getRacialSelfSpells()->filter(function ($spell) use ($raceName) {
            return $spell['races']->contains($raceName);
        })->first();

        return collect(array_filter([
            [
                'name' => 'Gaia\'s Watch',
                'description' => '+10% food production',
                'key' => 'gaias_watch',
                'mana_cost' => 2,
                'duration' => 12,
            ],
            [
                'name' => 'Ares\' Call',
                'description' => '+10% defensive power',
                'key' => 'ares_call',
                'mana_cost' => 2.5,
                'duration' => 12,
            ],
            [
                'name' => 'Midas Touch',
                'description' => '+10% platinum production',
                'key' => 'midas_touch',
                'mana_cost' => 2.5,
                'duration' => 12,
            ],
            [
                'name' => 'Mining Strength',
                'description' => '+10% ore production',
                'key' => 'mining_strength',
                'mana_cost' => 2,
                'duration' => 12,
            ],
            [
                'name' => 'Harmony',
                'description' => '+50% population growth',
                'key' => 'harmony',
                'mana_cost' => 2.5,
                'duration' => 12,
            ],
//            [
//                'name' => 'Surreal Perception',
//                'description' => 'Shows you the dominion upon receiving offensive spells or spy ops',
//                'key' => 'surreal_perception',
//                'mana_cost' => 4,
//                'duration' => 8,
//            ],
//            [
//                'name' => 'Energy Mirror',
//                'description' => '20% chance to reflect incoming spells',
//                'key' => '',
//                'mana_cost' => 3,
//                'duration' => 8,
//            ],
//            [
//                'name' => 'Fool\'s Gold',
//                'description' => 'Platinum theft protection for 10 hours, 22 hour recharge',
//                'key' => 'fools_gold',
//                'mana_cost' => 5,
//                'duration' => 10,
//                'cooldown' => 22, // todo
//            ],
            $racialSpell
        ]));
    }

    public function getRacialSelfSpells(): Collection
    {
        return collect([
            [
                'name' => 'Crusade',
                'description' => '+5% offensive power, and allows you to kill Undead',
                'key' => 'crusade',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Human', 'Nomad']),
            ],
            [
                'name' => 'Miner\'s Sight',
                'description' => '+20% ore production (not cumulative with Mining Strength)',
                'key' => 'miners_sight',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Dwarf']),
            ],
            [
                'name' => 'Killing Rage',
                'description' => '+10% offensive power',
                'key' => 'killing_rage',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Goblin']),
            ],
            [
                'name' => 'Alchemist Flame',
                'description' => '+15 alchemy platinum production',
                'key' => 'alchemist_flame',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Firewalker']),
            ],
            [
                'name' => 'Erosion',
                'description' => '20% of captured land re-zoned into water',
                'key' => 'erosion',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Lizardfolk', 'Merfolk']),
            ],
            [
                'name' => 'Blizzard',
                'description' => '+15% defensive strength (not cumulative with Ares Call)',
                'key' => 'blizzard',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Icekin']),
            ],
            [
                'name' => 'Mechanical Genius',
                'description' => '30% reduction of re-zoning costs',
                'key' => 'mechanical_genius',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Gnome']),
            ],
            [
                'name' => 'Unholy Ghost',
                'description' => 'Enemy draftees do not participate in battle due to extreme fear',
                'key' => 'unholy_ghost',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Dark Elf']),
            ],
            [
                'name' => 'Defensive Frenzy',
                'description' => '+20% defensive strength (not cumulative with Ares Call)',
                'key' => 'defensive_frenzy',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Halfling']),
            ],
            [
                'name' => 'Warsong',
                'description' => '+10% offensive power',
                'key' => 'warsong',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Sylvan']),
            ],
            [
                'name' => 'Regeneration',
                'description' => 'Reduces combat losses by 25%',
                'key' => 'regeneration',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Troll']),
            ],
            [
                'name' => 'Parasitic Hunger',
                'description' => 'Increases conversions by 3 percentage points',
                'key' => 'parasitic_hunger',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Undead']),
            ],
            [
                'name' => 'Gaia\'s Blessing',
                'description' => '+20% food production (not cumulative with Gaia\'s Watch), +10% lumber production',
                'key' => 'gaias_blessing',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Wood Elf']),
            ],
        ]);
    }

    public function getOffensiveSpells(): Collection
    {
        return $this->getInfoOpSpells()
            ->merge($this->getBlackOpSpells())
            ->merge($this->getWarSpells());
    }

    public function getInfoOpSpells(): Collection
    {
        return collect([
            [
                'name' => 'Clear Sight',
                'description' => 'Reveal status screen',
                'key' => 'clear_sight',
                'mana_cost' => 0.5,
            ],
//            [
//                'name' => 'Vision',
//                'description' => 'Reveal tech and heroes',
//                'key' => 'vision',
//                'mana_cost' => 0.5,
//            ],
            [
                'name' => 'Revelation',
                'description' => 'Reveal active spells',
                'key' => 'revelation',
                'mana_cost' => 1.2,
            ],
            [
                'name' => 'Clairvoyance',
                'description' => 'Reveal realm town crier',
                'key' => 'clairvoyance',
                'mana_cost' => 1.2,
            ],
//            [
//                'name' => 'Disclosure',
//                'description' => 'Reveal wonder',
//                'key' => 'disclosure',
//                'mana_cost' => 1.2,
//            ],
        ]);
    }

    public function getBlackOpSpells(): Collection
    {
        return collect([
            // plague
            // insect swarm
            // great flood
            // earthquake
        ]);
    }

    public function getWarSpells(): Collection
    {
        return collect([
            // fireball
            // lightning bolt
            // disband spies
        ]);
    }
}
