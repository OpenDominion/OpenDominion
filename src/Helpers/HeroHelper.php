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
                'coefficient' => 0.6,
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
                'name' => 'Scion',
                'key' => 'scion',
                'class_type' => 'advanced',
                'perk_type' => 'ops_power',
                'coefficient' => 1,
                'perks' => ['disarmament', 'martyrdom', 'revised_strategy'],
                'icon' => 'ra-ankh',
                'requirement_stat' => 'stat_attacking_success',
                'requirement_value' => 10,
                'starting_xp_stat' => 'prestige',
                'starting_xp_coefficient' => 1
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
        return HeroUpgrade::active()->with('perks')->get()->sortBy(['level', 'name'])->keyBy('key');
    }

    public function getHeroUpgradesByName(array $keys)
    {
        return HeroUpgrade::active()->whereIn('key', $keys)->get()->sortBy('name');
    }

    public function getHeroUpgradesByClass(string $class)
    {
        return $this->getHeroUpgrades()->filter(function ($bonus) use ($class) {
            return $bonus->classes === [] || in_array($class, $bonus->classes);
        });
    }

    public function getHeroUpgradePerkStrings()
    {
        return [
            'assassinate_draftees_damage' => '%+g%% assassinate draftee damage',
            'invasion_morale' => 'Invasion no longer reduces morale (75%%+ range only)',
            'land_spy_strength_cost' => 'Land Spy now costs 1%% spy strength',
            'martyrdom' => 'Reduces the cost of spy and wizard training for 48 hours',
            'offense' => '%+g%% offensive power',
            'raze_mod_building_discount' => 'Destroying military buildings (Gryphon Nests, Guard Towers, and Temples) awards discounted land',
            'tech_production_invasion' => '%+g%% research point gains from invasion',
            'tech_refund' => 'Reallocate techs (100%% refund for up to 5 techs and %g%% refund for remaining)',
        ];
    }

    public function getRequirementDisplay(array $class) {
        $stat = str_replace('_', ' ', str_replace('stat_', '', $class['requirement_stat']));
        $value = $class['requirement_value'];

        return sprintf('%s %s',
            $value,
            str_plural($stat, $value)
        );
    }

    public function getStartingExperienceDisplay(array $class) {
        $stat = str_replace('_', '', str_replace('stat_', '', $class['starting_xp_stat']));
        $coefficient = $class['starting_xp_coefficient'];

        return sprintf('%s%% of %s',
            $coefficient * 100,
            $stat
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

    public function getUpgradeIcon(int $level, ?HeroUpgrade $bonus)
    {
        if ($bonus === null) {
            return sprintf(
                '<i class="hero-icon fa fa-fw fa-lock" title="Level %s: Locked" data-toggle="tooltip"></i>',
                $level
            );
        }

        return sprintf(
            '<i class="hero-icon ra ra-fw %s" title="Level %s: %s<br>(%s)" data-toggle="tooltip"></i>',
            $bonus->icon,
            $level,
            $bonus->name,
            ucwords($bonus->type)
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
        $race = str_replace('-legacy', '', str_replace('-rework', '', $race));
        $filesystem = app(\Illuminate\Filesystem\Filesystem::class);
        try {
            $names_json = json_decode($filesystem->get(base_path("app/data/heroes/{$race}.json")));
        } catch (\Illuminate\Contracts\Filesystem\FileNotFoundException $e) {
            return [];
        }
        return $names_json->names;
    }
}
