<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;

class SpellHelper
{
    public function getSpellInfo(string $spell): array
    {
        return $this->getSpells()->filter(function ($value) use ($spell) {
            return ($value['key'] === $spell);
        })->first();
    }

    public function getSpells(): Collection
    {
        return collect($this->getSelfSpells()->toArray() + $this->getOffensiveSpells()->toArray());
    }

    public function getSelfSpells(): Collection
    {
        return collect([
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
            // todo: racial
        ]);
    }

    public function getOffensiveSpells(): Collection
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
            // todo: black ops
        ]);
    }
}
