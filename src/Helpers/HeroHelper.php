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
                'coefficient' => 0.3,
                'icon' => 'ra ra-gold-bar',
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'perk_type' => 'construction_cost',
                'coefficient' => -0.65,
                'icon' => 'ra ra-hand-saw',
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'perk_type' => 'military_cost',
                'coefficient' => -0.2,
                'icon' => 'ra ra-anvil',
            ],
            [
                'name' => 'Cleric',
                'key' => 'cleric',
                'perk_type' => 'fewer_casualties',
                'coefficient' => 1,
                'icon' => 'ra ra-health',
            ],
            [
                'name' => 'Farmer',
                'key' => 'farmer',
                'perk_type' => 'food_production',
                'coefficient' => 0.65,
                'icon' => 'ra ra-sickle',
            ],
            [
                'name' => 'Miner',
                'key' => 'miner',
                'perk_type' => 'gem_production',
                'coefficient' => 1,
                'icon' => 'ra ra-mining-diamonds',
            ],
            [
                'name' => 'Professor',
                'key' => 'professor',
                'perk_type' => 'tech_production',
                'coefficient' => 0.5,
                'icon' => 'ra ra-acid',
            ],
            [
                'name' => 'Spy',
                'key' => 'spy',
                'perk_type' => 'spy_strength',
                'coefficient' => 2,
                'icon' => 'ra ra-hood',
            ],
            [
                'name' => 'Wizard',
                'key' => 'wizard',
                'perk_type' => 'wizard_strength',
                'coefficient' => 2,
                'icon' => 'ra ra-pointy-hat',
            ],
        ])->keyBy('key');
    }

    public function getTradeDisplayName(string $key)
    {
        return $this->getTrades()[$key]['name'];
    }

    public function getTradeIconClass(string $key)
    {
        return $this->getTrades()[$key]['icon'];
    }

    public function getTradeHelpString(string $key)
    {
        $perk = $this->getTrades()[$key]['perk_type'];

        $helpStrings = [
            'construction_cost' => '%+4g%% construction platinum cost',
            'food_production' => '%+4g%% food production',
            'fewer_casualties' => '%+4g%% fewer casualties',
            'gem_production' => '%+4g%% gem production',
            'military_cost' => '%+4g%% military training cost',
            'platinum_production' => '%+4g%% platinum production',
            'tech_production' => '%+4g%% research point production',
            'spy_strength' => '%+4g%% spy power',
            'wizard_strength' => '%+4g%% wizard power',
        ];

        return $helpStrings[$perk] ?: null;
    }
}
