<?php

namespace OpenDominion\Helpers;

use OpenDominion\Models\HeroBonus;

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
                'perk_type' => 'explore_cost',
                'coefficient' => -1,
                'perks' => ['martyrdom', 'special_forces'],
                'icon' => 'ra-ankh'
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
            'spy_power' => '%+.2f%% spy power',
            'wizard_power' => '%+.2f%% wizard power',
        ];

        return $helpStrings[$perk] ?? null;
    }

    public function getHeroBonuses()
    {
        return HeroBonus::active()->with('perks')->get()->keyBy('key');
    }

    public function getHeroBonusPerkStrings()
    {
        return [
            'assassinate_draftees_damage' => '%+g%% assassinate draftee damage',
            'invasion_morale' => 'Invasions no longer reduce morale',
            'invasion_tech_gains' => '%+g research points gained from invasion',
            'land_spy_strength_cost' => 'Land Spy and Survey Dominion now cost 1%% spy strength',
            'martyrdom' => 'Reduces the cost of construction, rezoning, spy training, and wizard training for 48 hours after selecting Scion',
            'prestige_ops' => 'Prestige now modifies spy and wizard power instead of offensive power',
        ];
    }

    public function getBonusDescription(HeroBonus $heroBonus, string $separator = ', '): string
    {
        $perkTypeStrings = $this->getHeroBonusPerkStrings();

        $perkStrings = [];
        foreach ($heroBonus->perks as $perk) {
            if (isset($perkTypeStrings[$perk->key])) {
                $perkValue = (float)$perk->value;
                $perkStrings[] = sprintf($perkTypeStrings[$perk->key], $perkValue);
            }
        }

        return implode($separator, $perkStrings);
    }

    public function getBonusIcon(int $level, ?HeroBonus $bonus)
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
