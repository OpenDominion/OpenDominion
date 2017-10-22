<?php

namespace OpenDominion\Helpers;

class SpellHelper
{
    public function getSelfSpells(): array
    {
        return [
            [
                'name' => 'Gaia\'s Watch',
                'description' => '+10% food production',
                'key' => 'gaias_watch',
                'mana_cost' => 2,
            ],
            [
                'name' => 'Ares\' Call',
                'description' => '+10% defensive power',
                'key' => 'ares_call',
                'mana_cost' => 2.5,
            ],
            [
                'name' => 'Midas Touch',
                'description' => '+10% platinum production',
                'key' => 'midas_touch',
                'mana_cost' => 2.5,
            ],
            [
                'name' => 'Mining Strength',
                'description' => '+10% ore production',
                'key' => 'mining_strength',
                'mana_cost' => 2,
            ],
            [
                'name' => 'Harmony',
                'description' => '+50% population growth',
                'key' => 'harmony',
                'mana_cost' => 2.5,
            ],
            [
                'name' => 'Surreal Perception',
                'description' => 'Shows you the dominion upon receiving offensive spells or spy ops',
                'key' => 'surreal_perception',
                'mana_cost' => 4,
            ],
            [
                'name' => 'Energy Mirror',
                'description' => '20% chance to reflect incoming spells',
                'key' => '',
                'mana_cost' => 3,
            ],
            [
                'name' => 'Fool\'s Gold',
                'description' => 'Platinum theft protection for 10 hours, 22 hour recharge',
                'key' => 'fools_gold',
                'mana_cost' => 5,
            ],
        ];
    }
}
