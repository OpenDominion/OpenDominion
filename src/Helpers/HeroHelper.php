<?php

namespace OpenDominion\Helpers;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenDominion\Models\HeroBattle;
use OpenDominion\Models\HeroCombatant;
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
                'coefficient' => 0.25,
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
                'coefficient' => -0.75,
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
                'coefficient' => 0.1,
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
                'requirement_stat' => 'stat_attacking_success',
                'requirement_value' => 6
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
            'enemy_lightning_bolt_damage' => '%+g%% enemy lightning bolt damage',
            'enemy_spy_losses' => '%+g%% enemy spy losses on failed operations',
            'espionage_fails_hide_identity' => 'Failed spy ops no longer reveal your identity',
            'exchange_mana' => 'Mana can be converted into other resources',
            'fireball_damage' => '%+g%% fireball damage',
            'improved_energy_mirror' => '%+g%% additional damage reduction from Energy Mirror',
            'info_spell_cost' => '%+g%% cost of info spells',
            'self_spell_strength_cost' => '%+g wizard strength cost of self spells',
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

    public function getCombatActions(): Collection
    {
        return collect([
            'attack' => [
                'name' => 'Attack',
                'processor' => 'attack',
                'type' => 'hostile',
                'limited' => false,
                'special' => false,
                'messages' => [
                    'hit' => '%s deals %s damage to %s.',
                    'evaded' => '%s deals %s damage, but %s evades, reducing damage to %s.',
                    'countered' => '%s deals %s damage to %s, who then counters for %s damage.',
                    'evaded_countered' => '%s deals %s damage, but %s evades, reducing damage to %s, then %s counters for %s damage.',
                ]
            ],
            'defend' => [
                'name' => 'Defend',
                'processor' => 'defend',
                'type' => 'self',
                'limited' => false,
                'special' => false,
                'messages' => [
                    'defend' => '%s takes a defensive stance.'
                ]
            ],
            'focus' => [
                'name' => 'Focus',
                'processor' => 'focus',
                'type' => 'self',
                'limited' => true,
                'special' => false,
                'messages' => [
                    'focus' => '%s focuses their energy for the next attack.'
                ]
            ],
            'counter' => [
                'name' => 'Counter',
                'processor' => 'counter',
                'type' => 'self',
                'limited' => true,
                'special' => false,
                'messages' => [
                    'counter' => '%s prepares to counter-attack.'
                ]
            ],
            'recover' => [
                'name' => 'Recover',
                'processor' => 'recover',
                'type' => 'self',
                'limited' => true,
                'special' => false,
                'messages' => [
                    'recover' => '%s recovers %s health.'
                ]
            ],
            'volatile_mixture' => [
                'name' => 'Volatile Mixture',
                'processor' => 'volatile',
                'type' => 'hostile',
                'limited' => true,
                'special' => true,
                'class' => 'alchemist',
                'attributes' => [
                    'success_chance' => 0.8,
                    'attack_bonus' => 1.5,
                ],
                'messages' => [
                    'success' => '%s hurls an unstable concoction, dealing %s damage to %s.',
                    'backfire' => '%s\'s volatile mixture explodes prematurely! %s is caught in the blast, taking %s damage.',
                    'success_evaded' => '%s\'s explosive mixture detonates, but %s evades most of the blast, taking only %s damage.',
                    'success_countered' => '%s hurls an unstable concoction, dealing %s damage to %s, who then counters for %s damage.',
                    'success_evaded_countered' => '%s\'s explosive mixture detonates, but %s evades most of the blast, taking only %s damage, then %s counters for %s damage.',
                    'backfire_countered' => '%s\'s volatile mixture explodes prematurely! %s is caught in the blast for %s damage, then %s counters the distracted alchemist for %s damage.',
                ]
            ],
            'fortify' => [
                'name' => 'Fortify',
                'processor' => 'stat',
                'type' => 'self',
                'limited' => true,
                'special' => true,
                'class' => 'architect',
                'attributes' => [
                    'stat' => 'shield',
                    'value' => 20,
                ],
                'messages' => [
                    'stat' => '%s constructs defenses that will absorb 20 damage.'
                ]
            ],
            'forge' => [
                'name' => 'Forge',
                'processor' => 'stat',
                'type' => 'self',
                'limited' => true,
                'special' => true,
                'class' => 'blacksmith',
                'attributes' => [
                    'stat' => 'attack',
                    'value' => 1,
                ],
                'messages' => [
                    'stat' => '%s increases attack value by 1.'
                ]
            ],
            'tactical_awareness' => [
                'name' => 'Tactical Awareness',
                'processor' => 'stat',
                'type' => 'hostile',
                'limited' => true,
                'special' => true,
                'class' => 'engineer',
                'attributes' => [
                    'stat' => 'counter',
                    'value' => -2,
                ],
                'messages' => [
                    'stat' => '%s decreases %s\'s counter value by 2.'
                ]
            ],
            'hardiness' => [
                'name' => 'Hardiness',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'class' => 'farmer',
            ],
            'mending' => [
                'name' => 'Mending',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'class' => 'healer',
            ],
            'shadow_strike' => [
                'name' => 'Shadow Strike',
                'processor' => 'attack',
                'type' => 'hostile',
                'limited' => true,
                'special' => true,
                'class' => 'infiltrator',
                'attributes' => [
                    'evade' => false,
                    'defend' => -2,
                ],
                'messages' => [
                    'hit' => '%s strikes from the shadows, dealing %s damage to %s.',
                    'countered' => '%s strikes from the shadows, dealing %s damage to %s, who then counters for %s damage.',
                ]
            ],
            'crushing_blow' => [
                'name' => 'Crushing Blow',
                'processor' => 'attack',
                'type' => 'hostile',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'bonus_damage' => 15,
                    'defend' => 15,
                ],
                'messages' => [
                    'hit' => '%s delivers a crushing blow for %s damage to %s!',
                    'evaded' => '%s delivers a crushing blow for %s damage, but %s evades, reducing damage to %s!',
                    'countered' => '%s delivers a crushing blow for %s damage to %s, who then counters for %s damage!',
                    'evaded_countered' => '%s delivers a crushing blow for %s damage, but %s evades, reducing damage to %s, then %s counters for %s damage!',
                ]
            ],
            'combat_analysis' => [
                'name' => 'Combat Analysis',
                'processor' => 'stat',
                'type' => 'hostile',
                'limited' => true,
                'special' => true,
                'class' => 'scholar',
                'attributes' => [
                    'stat' => 'defense',
                    'value' => -1,
                ],
                'messages' => [
                    'stat' => '%s decreases %s\'s defense value by 1.'
                ]
            ],
            'last_stand' => [
                'name' => 'Last Stand',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'class' => 'scion',
            ],
            'channeling' => [
                'name' => 'Channeling',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'class' => 'sorcerer',
            ],
            'blade_flurry' => [
                'name' => 'Blade Flurry',
                'processor' => 'flurry',
                'type' => 'hostile',
                'limited' => true,
                'special' => true,
                'attributes' => [
                    'attacks' => 2,
                    'penalty' => 0.75,
                ],
                'messages' => [
                    'hit' => '%s unleashes a blade flurry, striking %s times for %s damage to %s.',
                    'evaded' => '%s unleashes a blade flurry, striking %s times for %s damage, but %s evades, reducing damage to %s.',
                    'countered' => '%s unleashes a blade flurry, striking %s times for %s damage to %s, who then counters %s times for %s damage.',
                    'evaded_countered' => '%s unleashes a blade flurry, striking %s times for %s damage, but %s evades, reducing damage to %s, then %s counters %s times for %s damage.',
                ]
            ],
            'undying' => [
                'name' => 'Undying',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'turns' => 5,
                ],
                'messages' => [
                    'undying' => '%s will return from the dead in 5 turns.'
                ]
            ],
            'undying_legion' => [
                'name' => 'Undying Legion',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
            ],
            'summon_skeleton' => [
                'name' => 'Summon Skeleton',
                'processor' => 'summon',
                'type' => 'self',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'enemy' => 'skeleton_warrior',
                    'turns' => 4,
                ],
                'messages' => [
                    'summon' => '%s has summoned a Skeleton Warrior.'
                ]
            ],
            'darkness' => [
                'name' => 'Darkness',
                'processor' => 'stat',
                'type' => 'self',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'turns' => 2,
                    'stat' => 'evasion',
                    'value' => 20,
                ],
                'messages' => [
                    'stat' => '%s increases evasion value by 20.'
                ]
            ],
            'elusive' => [
                'name' => 'Elusive',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
            ],
            'lifesteal' => [
                'name' => 'Lifesteal',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
            ],
            'tome_of_power' => [
                'name' => 'Tome of Power',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'turns_per_phase' => 3,
                    'max_phase' => 4,
                    'cycle_phases' => true,
                    'phases' => [
                        1 => [
                            'name' => 'Chapter of Blood',
                            'self_abilities' => ['weakened'],
                            'ally_abilities' => ['lifesteal'],
                            'message' => '%s turns to the Chapter of Blood!'
                        ],
                        2 => [
                            'name' => 'Chapter of Protection',
                            'self_abilities' => ['elusive'],
                            'ally_abilities' => ['arcane_shield'],
                            'message' => '%s turns to the Chapter of Protection!'
                        ],
                        3 => [
                            'name' => 'Chapter of Destruction',
                            'self_abilities' => ['weakened'],
                            'ally_abilities' => ['crushing_blow'],
                            'message' => '%s turns to the Chapter of Destruction!'
                        ],
                        4 => [
                            'name' => 'Chapter of Vengeance',
                            'self_abilities' => ['elusive'],
                            'ally_abilities' => ['retribution'],
                            'message' => '%s turns to the Chapter of Vengeance!'
                        ],
                    ]
                ]
            ],
            'power_source' => [
                'name' => 'Power Source',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
            ],
            'weakened' => [
                'name' => 'Weakened',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'defense' => -15,
                ],
            ],
            'retribution' => [
                'name' => 'Retribution',
                'processor' => null,
                'type' => 'passive',
                'limited' => false,
                'special' => true,
                'attributes' => [
                    'counter' => 15,
                ],
            ],
        ]);
    }

    public function getLimitedCombatActions(): Collection
    {
        return $this->getCombatActions()
            ->where('limited', true)
            ->keys();
    }

    public function getAvailableCombatActions(HeroCombatant $combatant = null): Collection
    {
        $combatActions = $this->getCombatActions();

        if ($combatant->hero_id === null) {
            // NPCs have access to all actions
            return $combatActions;
        }

        return $combatActions->filter(function ($action, $key) use ($combatant) {
            if (!$action['special']) {
                return true;
            }
            if ($combatant !== null && $combatant->abilities !== null) {
                return in_array($key, $combatant->abilities);
            }
            return false;
        });
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

    public function getSpecialAbilitiesTooltip(HeroCombatant $combatant): string
    {
        $descriptions = [
            'arcane_shield' => 'Arcane Shield: Defense value is increased by 10.',
            'blade_flurry' => 'Blade Flurry: Attack twice for 75% damage each time.',
            'channeling' => 'Channeling: Focus can be used while already active, stacking bonus damage.',
            'combat_analysis' => 'Combat Analysis: Decreases target\'s defense value by 1 for the remainder of the battle.',
            'crushing_blow' => 'Crushing Blow: Deals 15 additional damage if the target is not defending.',
            'darkness' => 'Darkness: Increases evasion value by 25.',
            'dying_light' => 'Dying Light: Upon death, reduces the Nightbringer\'s evasion to 0.',
            'enrage' => 'Enrage: When at 40 health or less, attack value is increased by 10.',
            'elusive' => 'Elusive: When evading a non-focused attack, damage is reduced to 0 instead of half.',
            'forge' => 'Forge: Increases attack value by 1 for the remainder of the battle.',
            'fortify' => 'Fortify: Prevent the next 20 non-counter damage dealt.',
            'hardiness' => 'Hardiness: Remain on 1 health the first time your health would be reduced below 1.',
            'last_stand' => 'Last Stand: When at 40 health or less, all combat stats are increased by 10%.',
            'lifesteal' => 'Lifesteal: Attacks heal for 50% of the damage dealt.',
            'mending' => 'Mending: Focus enhances your Recover ability, increasing healing.',
            'power_source' => 'Power Source: Upon destruction, weakens a connected ally.',
            'rally' => 'Rally: When at 40 health or less, defense value is increased by 5.',
            'retribution' => 'Retribution: Counter attack damage is increased by 15.',
            'shadow_strike' => 'Shadow Strike: Attack that cannot be evaded and deals +2 damage if the target is defending.',
            'summon_skeleton' => 'Summon: Summons a Skeleton Warrior every 4th turn.',
            'tactical_awareness' => 'Tactical Awareness: Reduces target\'s counter value by 2 for the remainder of the battle.',
            'tome_of_power' => 'Tome of Power: Cycles through 4 chapters every 3rd turn, granting different abilities each chapter.',
            'undying' => 'Undying: Returns from the dead 5 turns after being defeated.',
            'undying_legion' => 'Undying Legion: Immune to damage while any minions are alive.',
            'volatile_mixture' => 'Volatile Mixture: Attack for 150% damage, but 20% chance to hit yourself.',
            'weakened' => 'Weakened: Defense value is decreased by 15.',
        ];

        $combatantDescriptions = [];
        foreach ($combatant->abilities ?? [] as $ability) {
            if (isset($descriptions[$ability])) {
                $combatantDescriptions[] = $descriptions[$ability];
            }
        }

        return implode('<br><br>', $combatantDescriptions);
    }

    public function getCombatStrategies(): Collection
    {
        return collect([
            'balanced' => [
                'name' => 'Balanced',
                'type' => 'basic',
                'options' => ['attack' => 4, 'defend' => 1, 'focus' => 1, 'counter' => 1, 'recover' => 1]
            ],
            'aggressive' => [
                'name' => 'Aggressive',
                'type' => 'basic',
                'options' => ['attack' => 5, 'focus' => 3, 'counter' => 1, 'recover' => 1]
            ],
            'defensive' => [
                'name' => 'Defensive',
                'type' => 'basic',
                'options' => ['attack' => 3, 'defend' => 1, 'counter' => 1, 'recover' => 1]
            ],
            'attack' => [
                'name' => 'Mindless Attacker',
                'type' => 'npc',
                'options' => ['attack' => 1]
            ],
            'counter' => [
                'name' => 'Counter-Heavy',
                'type' => 'npc',
                'options' => ['attack' => 3, 'defend' => 1, 'counter' => 3, 'recover' => 1]
            ],
            'pirate' => [
                'name' => 'Pirate',
                'type' => 'npc',
                'options' => ['attack' => 2, 'blade_flurry' => 3, 'focus' => 1, 'counter' => 1, 'recover' => 1]
            ],
            'summoner' => [
                'name' => 'Summoner',
                'type' => 'npc',
                'options' => ['attack' => 0, 'defend' => 4, 'recover' => 1]
            ],
        ]);
    }

    public function canUseCombatAction(HeroCombatant $combatant, string $action): bool
    {
        $actionDefinitions = $this->getCombatActions();
        $actionDef = $actionDefinitions->get($action);

        if ($actionDef === null) {
            return false;
        }

        $queue = $combatant->actions ?? [];
        if (count($queue) > 0) {
            $lastAction = end($queue);
        } else {
            $lastAction = $combatant->last_action;
        }

        if ($actionDef['limited'] && $action == $lastAction) {
            return false;
        }

        if ($action == 'focus') {
            if (in_array('channeling', $combatant->abilities ?? [])) {
                return true;
            }
            if ($combatant->has_focus && count($queue) == 0) {
                return false;
            }
            // TODO: check for double focus without attack in between
        }

        return true;
    }

    public function getBattleResult(HeroBattle $battle): string
    {
        if (!$battle->finished) {
            return 'The battle is still in progress.';
        }

        if ($battle->winner_combatant_id === null) {
            $outcomes = collect([
                'The battle ended in a draw.',
                'Neither side could claim victory, what a nail-biter!',
                'Both heroes walked away, pride intact but egos bruised.',
                'The dust has settled, but the score remains even.',
                'It was a tie! The bards are still arguing about who was better.',
                'No winner, no loser—just a great story for the tavern.',
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
            ]);

            $winner = $battle->winner->name;
            $loser = $battle->combatants->where('id', '!=', $battle->winner_combatant_id)->first()->name;
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
            '<i class="hero-icon ra ra-fw %s" title="Level %s: %s<br>(%s)" data-toggle="tooltip"></i>',
            $upgrade->icon,
            $upgrade->level,
            $upgrade->name,
            ucwords($upgrade->type)
        );
    }

    public function getLockIcon(int $level)
    {
        return sprintf(
            '<i class="hero-icon ra ra-rw ra-padlock" title="Level %s: Locked" data-toggle="tooltip"></i>',
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
