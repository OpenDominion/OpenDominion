<?php

namespace OpenDominion\Helpers;

class HeroHelper
{
    public function getClasses()
    {
        return collect([
            [
                'name' => 'Warrior',
                'key' => 'warrior',
                'xp_bonus_type' => 'invasion',
            ],
            [
                'name' => 'Scout',
                'key' => 'scout',
                'xp_bonus_type' => 'exploration',
            ],
            [
                'name' => 'Thief',
                'key' => 'thief',
                'xp_bonus_type' => 'espionage',
            ],
            [
                'name' => 'Mage',
                'key' => 'mage',
                'xp_bonus_type' => 'magic',
            ]
        ])->keyBy('key');
    }

    public function getClassDisplayName(string $key)
    {
        return $this->getClasses()[$key]['name'];
    }

    public function getTrades()
    {
        return collect([
            [
                'name' => 'Alchemist',
                'key' => 'alchemist',
                'perk_type' => 'platinum_production',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'perk_type' => 'construction_cost',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'perk_type' => 'military_cost',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Cleric',
                'key' => 'cleric',
                'perk_type' => 'fewer_casualties',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Farmer',
                'key' => 'farmer',
                'perk_type' => 'food_production',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Miner',
                'key' => 'miner',
                'perk_type' => 'ore_production',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Professor',
                'key' => 'professor',
                'perk_type' => 'tech_production',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Spy',
                'key' => 'spy',
                'perk_type' => 'spy_strength',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
            [
                'name' => 'Wizard',
                'key' => 'wizard',
                'perk_type' => 'wizard_strength',
                'icon' => '',
                //'coefficient' => 2.1,
                //'maximum' => 10,
            ],
        ])->keyBy('key');
    }

    public function getTradeDisplayName(string $key)
    {
        return $this->getTrades()[$key]['name'];
    }
}
