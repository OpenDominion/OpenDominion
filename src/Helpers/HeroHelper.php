<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\HeroUpgrade;

class HeroHelper
{
    public function getClasses()
    {
        return collect([
            [
                'name' => 'Alchemist',
                'key' => 'alchemist',
                'class_type' => 'basic',
                'perk_type' => 'platinum_production',
                'coefficient' => 0.2,
                'icon' => 'ra-gold-bar'
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'class_type' => 'basic',
                'perk_type' => 'construction_cost',
                'coefficient' => -1.2,
                'icon' => 'ra-quill-ink'
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'class_type' => 'basic',
                'perk_type' => 'military_cost',
                'coefficient' => -0.25,
                'icon' => 'ra-anvil'
            ],
            [
                'name' => 'Engineer',
                'key' => 'engineer',
                'class_type' => 'basic',
                'perk_type' => 'invest_bonus',
                'coefficient' => 0.75,
                'icon' => 'ra-hammer'
            ],
            [
                'name' => 'Healer',
                'key' => 'healer',
                'class_type' => 'basic',
                'perk_type' => 'casualties',
                'coefficient' => -1,
                'icon' => 'ra-apothecary'
            ],
            [
                'name' => 'Infiltrator',
                'key' => 'infiltrator',
                'class_type' => 'basic',
                'perk_type' => 'spy_power',
                'coefficient' => 2,
                'icon' => 'ra-hood'
            ],
            [
                'name' => 'Sorcerer',
                'key' => 'sorcerer',
                'class_type' => 'basic',
                'perk_type' => 'wizard_power',
                'coefficient' => 2,
                'icon' => 'ra-pointy-hat'
            ],
            [
                'name' => 'Scholar',
                'key' => 'scholar',
                'class_type' => 'advanced',
                'perk_type' => 'tech_cost',
                'coefficient' => -0.75,
                'perks' => ['pursuit_of_knowledge'],
                'icon' => 'ra-graduate-cap',
                'requirement_stat' => 'resource_tech',
                'requirement_value' => 10000
            ],
            [
                'name' => 'Scion',
                'key' => 'scion',
                'class_type' => 'advanced',
                'perk_type' => 'ops_power',
                'coefficient' => 1,
                'perks' => ['disarmament', 'martyrdom', 'revised_strategy'],
                'icon' => 'ra-ankh',
                'requirement_stat' => 'prestige',
                'requirement_value' => 350
            ]
        ])->keyBy('key');
    }

    public function getAdvancedClasses()
    {
        return $this->getClasses()->where('class_type', 'advanced');
    }

    public function getBasicClasses()
    {
        return $this->getClasses()->where('class_type', 'basic');
    }

    public function getClassDisplayName(string $key)
    {
        return $this->getClasses()[$key]['name'];
    }

    public function getClassIcon(string $key)
    {
        return $this->getClasses()[$key]['icon'];
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
            'ops_power' => '%+.2f%% spy and wizard power',
            'spy_power' => '%+.2f%% spy power',
            'wizard_power' => '%+.2f%% wizard power',
        ];

        return $helpStrings[$perk] ?? null;
    }

    public function getHeroUpgrades()
    {
        return HeroUpgrade::with('perks')->get()->sortBy(['level', 'type', 'classes'])->keyBy('key');
    }

    public function getHeroUpgradesByName(array $keys)
    {
        return HeroUpgrade::whereIn('key', $keys)->get()->sortBy('name');
    }

    public function getHeroUpgradesByClass(string $class)
    {
        return $this->getHeroUpgrades()->filter(function ($upgrade) use ($class) {
            return $upgrade->classes === [] || in_array($class, $upgrade->classes);
        })->sortBy(['level', 'name']);
    }

    public function getHeroUpgradePerkStrings()
    {
        return [
            // Magic
            'enemy_lightning_bolt_damage' => '%+g%% enemy lightning bolt damage',
            'enemy_spy_losses' => '%+g%% enemy spy losses on failed operations',
            'espionage_fails_hide_identity' => 'Failed spy ops no longer reveal your identity',
            'fireball_damage' => '%+g%% fireball damage',
            'improved_energy_mirror' => '%+g%% additional damage reduction from Energy Mirror',
            'info_spell_cost' => '%+g%% cost of info spells',
            'spell_fails_hide_identity' => 'Failed spells no longer reveal your identity',

            // Items
            'assassinate_draftees_damage' => '%+g%% assassinate draftee damage',
            'cyclone_damage' => '%+g%% cyclone damage',
            'invasion_morale' => '%+g%% morale loss from invasion',
            'land_spy_strength_cost' => 'Survey Dominion and Land Spy now cost 1%% spy strength',
            'retal_prestige' => '%+g prestige gains from invasion if the target has attacked your realm (doubled if in the last 24 hours)',
            'tech_production_invasion' => '%+g%% research point gains from invasion',
            'wonder_attack_damage' => '%+g%% attack damage against wonders',

            // Advanced
            'invest_bonus' => '%+g%% castle investment bonus',
            'martyrdom' => 'Reduces the cost of spy and wizard training by 1%% per %g prestige (max 50%%) for 24 hours',
            'offense' => '%+g%% offensive power',
            'raze_mod_building_discount' => 'Destroying military buildings (Gryphon Nests, Guard Towers, and Temples) awards discounted land',
            'tech_production' => '%+g%% research point production',
            'tech_refund' => 'Reset all techs, then gain RP to unlock up to 5 techs lost plus  %g%% of the remaining techs lost',
        ];
    }

    public function getRequirementDisplay(array $class) {
        $stat = str_replace('stat_', '', $class['requirement_stat']);
        $value = $class['requirement_value'];

        return sprintf(
            '%s %s',
            number_format($value),
            dominion_attr_display($stat, $value)
        );
    }

    public function getUpgradeDescription(HeroUpgrade $heroUpgrade, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getHeroUpgradePerkStrings();

        $perkStrings = [];
        foreach ($heroUpgrade->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->value;
                $perkStrings[] = sprintf($perkTypeStrings[$perk->key], $perkValue);
            }
        }

        return implode($separator, $perkStrings);
    }

    public function getUpgradeIcon(int $level, ?HeroUpgrade $upgrade)
    {
        if ($upgrade === null) {
            return sprintf(
                '<i class="hero-icon ra ra-rw ra-padlock" title="Level %s: Locked" data-toggle="tooltip"></i>',
                $level
            );
        }

        return sprintf(
            '<i class="hero-icon ra ra-fw %s" title="Level %s: %s<br>(%s)" data-toggle="tooltip"></i>',
            $upgrade->icon,
            $level,
            $upgrade->name,
            ucwords($upgrade->type)
        );
    }

    /**
     * Returns a list of race-specific hero names.
     *
     * @param string $race
     * @return array
     */
    public function getNamesByRace(string $race): array
    {
        $race = str_replace('-', '', str_replace('-legacy', '', str_replace('-rework', '', $race)));
        $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
        try {
            $names_json = json_decode($filesystem->get(base_path("app/data/heroes/{$race}.json")));
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            return [];
        }
        return $names_json->names;
    }
}
