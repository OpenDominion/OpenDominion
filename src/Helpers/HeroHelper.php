<?php

namespace OpenDominion\Helpers;

class HeroHelper
{
    public function getClasses()
    {
        return collect([
            [
                'name' => 'Alchemist',
                'key' => 'alchemist',
                'perk_type' => 'platinum_production',
                'coefficient' => 0.2,
                'icon' => 'ra ra-gold-bar'
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'perk_type' => 'construction_cost',
                'coefficient' => -1.2,
                'icon' => 'ra ra-quill-ink'
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'perk_type' => 'military_cost',
                'coefficient' => -0.25,
                'icon' => 'ra ra-anvil'
            ],
            [
                'name' => 'Engineer',
                'key' => 'engineer',
                'perk_type' => 'invest_bonus',
                'coefficient' => 0.6,
                'icon' => 'ra ra-hammer'
            ],
            [
                'name' => 'Healer',
                'key' => 'healer',
                'perk_type' => 'casualties',
                'coefficient' => -1,
                'icon' => 'ra ra-apothecary'
            ],
            [
                'name' => 'Infiltrator',
                'key' => 'infiltrator',
                'perk_type' => 'spy_power',
                'coefficient' => 2,
                'icon' => 'ra ra-hood'
            ],
            [
                'name' => 'Sorcerer',
                'key' => 'sorcerer',
                'perk_type' => 'wizard_power',
                'coefficient' => 2,
                'icon' => 'ra ra-pointy-hat'
            ],
        ])->keyBy('key');
    }

    /**
     * Returns the passive hero perk type.
     *
     * @param string $class
     * @return float
     */
    public function getPassivePerkType(string $class): string
    {
        return $this->getClasses()[$class]['perk_type'];
    }

    public function getClassDisplayName(string $key)
    {
        return $this->getClasses()[$key]['name'];
    }

    public function getClassIcon(string $key)
    {
        return $this->getClasses()[$key]['icon'];
    }

    public function getPassiveHelpString(string $key)
    {
        $perk = $this->getClasses()[$key]['perk_type'];

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

    /**
     * Returns a list of race-specific hero names.
     *
     * @param string $race
     * @return array
     */
    public function getNamesByRace(string $race): array
    {
        $race = str_replace('-rework', '', $race);
        $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
        try {
            $names_json = json_decode($filesystem->get(base_path("app/data/heroes/{$race}.json")));
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            return [];
        }
        return $names_json->names;
    }
}
