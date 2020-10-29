<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use OpenDominion\Models\Race;

class SpellHelper
{
    public function getSpellInfo(string $spellKey): array
    {
        return $this->getSpells()->filter(function ($spell) use ($spellKey) {
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

    public function isHostileSpell(string $spellKey): bool
    {
        return $this->getHostileSpells()->filter(function ($spell) use ($spellKey) {
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

    public function getSpells(Race $race = null): Collection
    {
        return $this->getSelfSpells($race)
            ->merge($this->getRacialSelfSpells())
            ->merge($this->getOffensiveSpells());
    }

    public function getSelfSpells(?Race $race): Collection
    {
        $spells = collect(array_filter([
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
            [
                'name' => 'Fool\'s Gold',
                'description' => 'Platinum theft protection for 10 hours, 20 hour recharge',
                'key' => 'fools_gold',
                'mana_cost' => 5,
                'duration' => 10,
                'cooldown' => 20,
            ],
            [
                'name' => 'Surreal Perception',
                'description' => 'Reveals the dominion casting offensive spells or committing spy ops against you for 12 hours',
                'key' => 'surreal_perception',
                'mana_cost' => 3,
                'duration' => 12,
            ],
            [
                'name' => 'Energy Mirror',
                'description' => '20% chance to reflect incoming offensive spells for 12 hours',
                'key' => 'energy_mirror',
                'mana_cost' => 4,
                'duration' => 12,
            ]
        ]));

        if($race !== null){
            $racialSpell = $this->getRacialSelfSpell($race);
            $spells->push($racialSpell);
        }

        return $spells;
    }

    public function getRacialSelfSpell(Race $race) {
        $raceName = $race->name;
        return $this->getRacialSelfSpells()->filter(function ($spell) use ($raceName) {
            return $spell['races']->contains($raceName);
        })->first();
    }

    public function getRacialSelfSpells(): Collection
    {
        return collect([
            [
                'name' => 'Crusade',
                'description' => '+5% offensive power and allows you to kill Spirit/Undead',
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
                'races' => collect(['Dwarf', 'Gnome']),
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
                'races' => collect([]),
            ],
            [
                'name' => 'Bloodrage',
                'description' => '+10% offensive power, +10% offensive casualties',
                'key' => 'bloodrage',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Orc']),
            ],
            [
                'name' => 'Unholy Ghost',
                'description' => 'Enemy draftees do not participate in battle',
                'key' => 'unholy_ghost',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Dark Elf']),
            ],
            [
                'name' => 'Defensive Frenzy',
                'description' => '+20% defensive power (not cumulative with Ares Call)',
                'key' => 'defensive_frenzy',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Halfling']),
            ],
            [
                'name' => 'Howling',
                'description' => '+10% offensive power, +10% defensive power (not cumulative with Ares Call)',
                'key' => 'howling',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Kobold']),
            ],
            [
                'name' => 'Verdant Bloom',
                'description' => '35% of captured land re-zoned into forest',
                'key' => 'verdant_bloom',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Sylvan']),
            ],
            [
                'name' => 'Warsong',
                'description' => '+10% offensive power',
                'key' => 'warsong',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect([]),
            ],
            [
                'name' => 'Regeneration',
                'description' => '-25% combat losses',
                'key' => 'regeneration',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Troll']),
            ],
            [
                'name' => 'Parasitic Hunger',
                'description' => '+50% conversion rate',
                'key' => 'parasitic_hunger',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Lycanthrope', 'Spirit', 'Undead']),
            ],
            [
                'name' => 'Gaia\'s Blessing',
                'description' => '+20% food production (not cumulative with Gaia\'s Watch), +10% lumber production',
                'key' => 'gaias_blessing',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Wood Elf']),
            ],
            [
                'name' => 'Nightfall',
                'description' => '+5% offensive power',
                'key' => 'nightfall',
                'mana_cost' => 5,
                'duration' => 12,
                'races' => collect(['Nox']),
            ],
        ]);
    }

    public function getOffensiveSpells(): Collection
    {
        return $this->getInfoOpSpells()
            ->merge($this->getBlackOpSpells())
            ->merge($this->getWarSpells())
            ->merge($this->getWonderSpells());
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
            [
                'name' => 'Vision',
                'description' => 'Reveal tech and heroes',
                'key' => 'vision',
                'mana_cost' => 1,
            ],
            [
                'name' => 'Revelation',
                'description' => 'Reveal active spells',
                'key' => 'revelation',
                'mana_cost' => 1,
            ],
//            [
//                'name' => 'Clairvoyance',
//                'description' => 'Reveal realm town crier',
//                'key' => 'clairvoyance',
//                'mana_cost' => 1.2,
//            ],
//            [
//                'name' => 'Disclosure',
//                'description' => 'Reveal wonder',
//                'key' => 'disclosure',
//                'mana_cost' => 1.2,
//            ],
        ]);
    }

    public function getHostileSpells(): Collection
    {
        return $this->getBlackOpSpells()
            ->merge($this->getWarSpells());
    }

    public function getBlackOpSpells(): Collection
    {
        return collect([
            [
                'name' => 'Plague',
                'description' => 'Slows population growth',
                'key' => 'plague',
                'mana_cost' => 3,
                'duration' => 8,
            ],
            [
                'name' => 'Insect Swarm',
                'description' => 'Slows food production',
                'key' => 'insect_swarm',
                'mana_cost' => 3,
                'duration' => 8,
            ],
            [
                'name' => 'Great Flood',
                'description' => 'Slows boat production',
                'key' => 'great_flood',
                'mana_cost' => 3,
                'duration' => 8,
            ],
            [
                'name' => 'Earthquake',
                'description' => 'Slows mine production',
                'key' => 'earthquake',
                'mana_cost' => 3,
                'duration' => 8,
            ],
        ]);
    }

    public function getWarSpells(): Collection
    {
        return collect([
            [
                'name' => 'Disband Spies',
                'description' => 'Turns spies into draftees',
                'key' => 'disband_spies',
                'mana_cost' => 4.3,
                'decreases' => ['military_spies'],
                'increases' => ['military_draftees'],
                'percentage' => 1.5,
            ],
            [
                'name' => 'Fireball',
                'description' => 'Kills peasants and destroys crops',
                'key' => 'fireball',
                'mana_cost' => 3,
                'decreases' => ['peasants', 'resource_food'],
                'percentage' => 2.5,
            ],
            [
                'name' => 'Lightning Bolt',
                'description' => 'Destroys resources invested in castle',
                'key' => 'lightning_bolt',
                'mana_cost' => 3.5,
                'decreases' => [
                    'improvement_keep',
                    'improvement_towers',
                    'improvement_forges',
                    'improvement_walls',
                ],
                'percentage' => 0.40,
            ],
        ]);
    }

    public function getWonderSpells(): Collection
    {
        return collect([
            [
                'name' => 'Cyclone',
                'description' => 'Deals damage to a wonder',
                'key' => 'cyclone',
                'mana_cost' => 3.5,
                'icon_class' => 'ra ra-tornado',
                'damage_multiplier' => 5,
            ],
        ]);
    }
}
