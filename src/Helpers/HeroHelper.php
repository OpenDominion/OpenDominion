<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenDominion\Models\HeroBattle;
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
                'coefficient' => 0.3,
                'icon' => 'ra-gold-bar'
            ],
            [
                'name' => 'Architect',
                'key' => 'architect',
                'class_type' => 'basic',
                'perk_type' => 'construction_cost',
                'coefficient' => -1.25,
                'icon' => 'ra-quill-ink'
            ],
            [
                'name' => 'Blacksmith',
                'key' => 'blacksmith',
                'class_type' => 'basic',
                'perk_type' => 'military_cost',
                'coefficient' => -0.2,
                'icon' => 'ra-anvil'
            ],
            [
                'name' => 'Engineer',
                'key' => 'engineer',
                'class_type' => 'basic',
                'perk_type' => 'invest_bonus',
                'coefficient' => 0.5,
                'icon' => 'ra-hammer'
            ],
            [
                'name' => 'Farmer',
                'key' => 'farmer',
                'class_type' => 'basic',
                'perk_type' => 'food_production',
                'coefficient' => 1.5,
                'icon' => 'ra-sprout'
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
                'coefficient' => 2.5,
                'icon' => 'ra-hood'
            ],
            [
                'name' => 'Sorcerer',
                'key' => 'sorcerer',
                'class_type' => 'basic',
                'perk_type' => 'wizard_power',
                'coefficient' => 2.5,
                'icon' => 'ra-pointy-hat'
            ],
            [
                'name' => 'Scholar',
                'key' => 'scholar',
                'class_type' => 'advanced',
                'perk_type' => 'max_population',
                'coefficient' => 0.15,
                'perks' => ['pursuit_of_knowledge'],
                'icon' => 'ra-graduate-cap',
                'requirement_stat' => 'techCount',
                'requirement_value' => 2
            ],
            [
                'name' => 'Scion',
                'key' => 'scion',
                'class_type' => 'advanced',
                'perk_type' => 'explore_cost',
                'coefficient' => -0.25,
                'perks' => ['disarmament', 'martyrdom', 'revised_strategy'],
                'icon' => 'ra-ankh',
                'requirement_stat' => 'stat_total_land_conquered',
                'requirement_value' => 500
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

    public function getPassiveHelpString(string $perkType)
    {
        $helpStrings = [
            'casualties' => '%+.2f%% casualties',
            'construction_cost' => '%+.2f%% construction cost',
            'explore_cost' => '%+.2f%% exploring platinum cost',
            'food_production' => '%+.2f%% food production',
            'gem_production' => '%+.2f%% gem production',
            'invest_bonus' => '%+.2f%% castle investment bonus',
            'max_population' => '%+.2f%% maximum population',
            'military_cost' => '%+.2f%% military training cost',
            'platinum_production' => '%+.2f%% platinum production',
            'tech_production' => '%+.2f%% research point production',
            'ops_power' => '%+.2f%% spy and wizard power',
            'spy_power' => '%+.2f%% spy power',
            'wizard_power' => '%+.2f%% wizard power',
        ];

        return $helpStrings[$perkType] ?? null;
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
            // Doctrine
            'xp_from_land_gain_bonus' => 'Experience gains from invasion and exploration are doubled',
            'xp_from_ops_bonus' => 'Experience gains from magic and espionage are doubled',
            'xp_from_ops_penalty' => 'Experience cannot be gained from magic and espionage',

            // Magic
            'arcane_conduit_mana_cost' => '%+g%% Arcane Conduit mana cost',
            'break_ward_duration_reduction' => 'Break Ward removes %g%% more duration',
            'enemy_lightning_bolt_damage' => '%+g%% enemy lightning bolt damage',
            'enemy_spy_losses' => '%+g%% enemy spy losses on failed operations',
            'espionage_fails_hide_identity' => 'Failed spy ops no longer reveal your identity',
            'exchange_mana' => 'Mana can be converted into other resources',
            'fireball_damage' => '%+g%% fireball damage',
            'fireball_damage_resource_food' => '%+g%% food destroyed by Fireball',
            'hostile_spell_duration' => 'Black Op spells you cast last %g hours longer',
            'improved_energy_mirror' => '%+g%% additional damage reduction from Energy Mirror',
            'info_spell_cost' => '%+g%% cost of info spells',
            'info_spell_valuables_chance' => '%+g%% chance to discover valuables with info spells',
            'lightning_bolt_damage_improvement_keep' => '%+g%% Lightning Bolt damage to Keep',
            'magic_ward_duration' => '%+g hours Magic Ward duration',
            'magic_ward_mana_absorption' => 'While Magic Ward is active, gain %g%% of the mana spent on war spells cast at you',
            'magic_ward_penetration' => '%g%% of enemy Magic Ward damage reduction ignored',
            'mana_burn_strength_cost' => '%+g%% Mana Burn wizard strength cost',
            'repair_castle_mana_cost' => '%+g%% Repair Castle mana cost',
            'resolve_gain' => '%+g%% Resolve gains',
            'revive_peasants_mana_cost' => '%+g%% Revive Peasants mana cost',
            'self_spell_strength_cost' => '%+g wizard strength cost of self spells',
            'silence_strength_cost' => '%+g%% Silence wizard strength cost',
            'spell_fails_hide_identity' => 'Failed spells no longer reveal your identity',

            // Items
            'assassinate_draftees_damage' => '%+g%% assassinate draftee damage',
            'cyclone_damage' => '%+g%% cyclone damage',
            'enemy_spell_duration' => '%+g enemy spell duration',
            'invasion_morale' => '%+g%% morale loss from invasion',
            'land_spy_strength_cost' => 'Survey Dominion and Land Spy now cost 1%% spy strength',
            'retal_prestige' => '%+g prestige gains from invasion if the target realm has attacked your realm (doubled if in the last 24 hours)',
            'tech_production_invasion' => '%+g%% research point gains from invasion',
            'wonder_attack_damage' => '%+g%% attack damage against wonders',

            // Advanced
            'invest_bonus' => '%+g%% castle investment bonus',
            'martyrdom' => 'Reduces the cost of spy and wizard training by 1%% per %g prestige (max 50%%) for 24 hours',
            'offense' => '%+g%% offensive power',
            'raze_mod_building_discount' => 'Destroying military buildings (Docks, Gryphon Nests, Guard Towers, Smithies, and Temples) awards discounted land',
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

    public function getCombatUpgradeDescription(HeroUpgrade $heroUpgrade, string $separator = ', '): string
    {
        $perkStrings = [];
        foreach ($heroUpgrade->perks as $perk) {
            if (Str::startsWith($perk->key, 'combat_')) {
                $perkValue = (float)$perk->value;
                $stat = Str::replaceFirst('combat_', '', $perk->key);
                $perkStrings[] = sprintf('%+g %s', $perkValue, ucwords($stat));
            }
        }

        return implode($separator, $perkStrings);
    }

    public function getCombatStatTooltip(string $stat): string
    {
        $combatStats = [
            'health' => 'Current and maximum health',
            'attack' => 'Attack damage, reduced by defense of opponent',
            'defense' => 'Reduce incoming attack damage by this amount, doubled while defending',
            'evasion' => 'Chance to evade an attack is equal to this percentage',
            'focus' => 'Focus increases attack damage by this amount',
            'counter' => 'Counter attack damage is increased by this amount',
            'recover' => 'Heal damage equal to this amount',
        ];

        return $combatStats[$stat];
    }

    public function getBattleResult(HeroBattle $battle): string
    {
        if (!$battle->finished) {
            return 'The battle is still in progress.';
        }

        if ($battle->isDraw()) {
            $outcomes = collect([
                'The battle ended in a draw.',
                'Neither side could claim victory, what a nail-biter!',
                'Both heroes walked away, pride intact but egos bruised.',
                'The dust has settled, but the score remains even.',
                'It was a tie! The bards are still arguing about who was better.',
                'No winner, no loser—just a great story for the tavern.',
                'An impasse! Both heroes collapsed from exhaustion.',
                'Steel met steel until neither blade could swing again. A stalemate for the ages.',
                'The arena floor is cracked, the weapons shattered, and still no victor. Incredible.',
                'A draw so perfectly matched, the bookmakers are convinced it was rigged.',
            ]);

            $winner = null;
            $loser = null;
        } else {
            $outcomes = collect([
                '%1$s emerged victorious, basking in the glory of battle!',
                '%2$s was utterly defeated, their dreams dashed upon the battlefield.',
                'With a mighty roar, %1$s crushed their foe beneath their heel.',
                'The crowd cheered as %1$s claimed a legendary triumph!',
                '%1$s outwitted and outlasted their opponent, seizing the day!',
                'A stunning upset! %2$s never saw it coming.',
                'Victory was sweet for %1$s, who now stands tall among heroes.',
                '%2$s will remember this loss for ages to come.',
                'A tale of defeat for %2$s, sung by bards as a warning.',
                '%1$s\'s cunning and strength proved too much to overcome.',
                'The fates smiled on %1$s, granting them a glorious win.',
                'A crushing blow! %2$s was left reeling in the aftermath.',
                '%1$s\'s legend grows with every victory.',
                'The gods turned their backs on %2$s today.',
                'A masterful display by %1$s, leaving no doubt of their prowess.',
                'The dust settles, and %1$s stands alone as the victor.',
                'A bitter defeat for %2$s, but perhaps a lesson learned.',
                'In a shocking twist, %2$s tripped over their own feet and handed victory to %1$s.',
                '%1$s won so convincingly, the spectators asked for an autograph.',
                'Rumor has it %2$s is still looking for their dignity somewhere on the battlefield.',
                '%1$s carved their name into the arena stone with %2$s\'s own weapon.',
                'The crowd fell silent as %1$s delivered the final, merciless strike.',
                '%2$s put up a valiant fight, but %1$s was simply on another level today.',
                '%1$s fought with the fury of a thousand storms. %2$s never stood a chance.',
                'They say %2$s was seen weeping into their ale at the tavern afterward.',
                '%1$s dismantled %2$s so thoroughly, the healers weren\'t sure where to start.',
                'A flawless performance by %1$s. The scribes ran out of superlatives.',
                '%1$s made it look effortless, but the marks on their shield tell a different story.',
                '%2$s\'s squire has already posted a notice seeking new employment.',
                'The court jester declared %2$s the funniest act he\'s seen all season.',
            ]);

            $winner = $battle->winnerLabel();
            $loser = $battle->combatants
                ->where('team', '!=', $battle->winning_team)
                ->groupBy('team')
                ->map(fn ($members) => $members->pluck('name')->implode(' & '))
                ->implode(' and ');
        }

        // Use deterministic selection
        $seconds = $battle->updated_at->second;
        $index = $seconds % $outcomes->count();

        return sprintf(
            $outcomes->get($index),
            $winner,
            $loser
        );
    }

    public function getUpgradeIcon(HeroUpgrade $upgrade)
    {
        return sprintf(
            '<i class="hero-icon ra ra-fw %s" title="Level %s: %s<br>(%s)" data-bs-toggle="tooltip"></i>',
            $upgrade->icon,
            $upgrade->level,
            $upgrade->name,
            ucwords($upgrade->type)
        );
    }

    public function getLockIcon(int $level)
    {
        return sprintf(
            '<i class="hero-icon ra ra-rw ra-padlock" title="Level %s: Locked" data-bs-toggle="tooltip"></i>',
            $level
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
