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
                'coefficient' => 0.4,
                'icon' => 'ra ra-gold-bar',
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'perk_type' => 'construction_cost',
                'coefficient' => -1.2,
                'icon' => 'ra ra-quill-ink',
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'perk_type' => 'military_cost',
                'coefficient' => -0.25,
                'icon' => 'ra ra-anvil',
            ],
            [
                'name' => 'Engineer',
                'key' => 'engineer',
                'perk_type' => 'invest_bonus',
                'coefficient' => 0.6,
                'icon' => 'ra ra-hammer',
            ],
            [
                'name' => 'Healer',
                'key' => 'healer',
                'perk_type' => 'casualties',
                'coefficient' => -1,
                'icon' => 'ra ra-health',
            ],
            [
                'name' => 'Infiltrator',
                'key' => 'infiltrator',
                'perk_type' => 'spy_power',
                'coefficient' => 2,
                'icon' => 'ra ra-hood',
            ],
            [
                'name' => 'Sorcerer',
                'key' => 'sorcerer',
                'perk_type' => 'wizard_power',
                'coefficient' => 2,
                'icon' => 'ra ra-pointy-hat',
            ],
        ])->keyBy('key');
    }

    /**
     * Returns the trade's perk type.
     *
     * @param string $trade
     * @return float
     */
    public function getTradePerkType(string $trade): string
    {
        return $this->getTrades()[$trade]['perk_type'];
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
            'casualties' => '%+.2f%% casualties',
            'construction_cost' => '%+.2f%% construction platinum cost',
            'food_production' => '%+.2f%% food production',
            'gem_production' => '%+.2f%% gem production',
            'invest_bonus' => '%+.2f%% castle investment bonus',
            'military_cost' => '%+.2f%% military training cost',
            'platinum_production' => '%+.2f%% platinum production',
            'tech_production' => '%+.2f%% research point production',
            'spy_power' => '%+.2f%% spy power',
            'wizard_power' => '%+.2f%% wizard power',
        ];

        return $helpStrings[$perk] ?: null;
    }
}
