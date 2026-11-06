<?php

namespace OpenDominion\HeroCombat\Content\Loadouts;

use OpenDominion\HeroCombat\Engine\Stats\Stat;

/**
 * Every technique, ability and piece of gear a hero can equip. A class's signature is an
 * ability or a technique gated by that class's level; everything else is gated by combat level.
 */
class HeroLoadoutOptions
{
    public const TECHNIQUE_I_LEVEL = 3;
    public const UNIVERSAL_ABILITY_LEVEL = 5;
    public const TECHNIQUE_II_LEVEL = 7;
    public const GEAR_LEVEL = 3;
    public const SECOND_GEAR_LEVEL = 7;

    /**
     * @return LoadoutOption[]
     */
    public static function all(): array
    {
        return [
            new LoadoutOption('battle_fury', LoadoutSlot::Technique, self::TECHNIQUE_I_LEVEL, 'ra-axe'),
            new LoadoutOption('restoration', LoadoutSlot::Technique, self::TECHNIQUE_I_LEVEL, 'ra-heart-bottle'),
            new LoadoutOption('shadow_dance', LoadoutSlot::Technique, self::TECHNIQUE_I_LEVEL, 'ra-player-dodge'),
            new LoadoutOption('backstab', LoadoutSlot::Technique, self::TECHNIQUE_I_LEVEL, 'ra-dripping-knife'),
            new LoadoutOption('feint', LoadoutSlot::Technique, self::TECHNIQUE_I_LEVEL, 'ra-spinning-sword'),
            new LoadoutOption('mending', LoadoutSlot::Technique, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-apothecary'),
            new LoadoutOption('channeling', LoadoutSlot::Technique, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-pointy-hat'),
            new LoadoutOption('hardiness', LoadoutSlot::Technique, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-sprout'),

            new LoadoutOption('shield_bash', LoadoutSlot::Technique, self::TECHNIQUE_II_LEVEL, 'ra-round-shield'),
            new LoadoutOption('riposte', LoadoutSlot::Technique, self::TECHNIQUE_II_LEVEL, 'ra-crossed-swords'),
            new LoadoutOption('spellbrand', LoadoutSlot::Technique, self::TECHNIQUE_II_LEVEL, 'ra-focused-lightning'),
            new LoadoutOption('second_wind', LoadoutSlot::Technique, self::TECHNIQUE_II_LEVEL, 'ra-feather-wing'),

            new LoadoutOption('volatile_mixture', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-gold-bar'),
            new LoadoutOption('fortify', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-quill-ink'),
            new LoadoutOption('forge', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-anvil'),
            new LoadoutOption('caltrops', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-hammer'),
            new LoadoutOption('shadow_strike', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-hood'),
            new LoadoutOption('combat_analysis', LoadoutSlot::Ability, HeroClassLoadouts::UNLOCK_CLASS_LEVEL, 'ra-graduate-cap'),
            new LoadoutOption('blade_flurry', LoadoutSlot::Ability, self::UNIVERSAL_ABILITY_LEVEL, 'ra-daggers'),
            new LoadoutOption('whirlwind', LoadoutSlot::Ability, self::UNIVERSAL_ABILITY_LEVEL, 'ra-dervish-swords'),

            new LoadoutOption('greatsword', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-sword', 'Greatsword', [
                Stat::Attack->value => 2,
                Stat::Evasion->value => -10,
            ]),
            new LoadoutOption('plate_armor', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-knight-helmet', 'Plate Armor', [
                Stat::Defense->value => 1,
                Stat::Evasion->value => -3,
            ]),
            new LoadoutOption('swordbreaker', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-bowie-knife', 'Swordbreaker Dagger', [
                Stat::Counter->value => 6,
            ]),
            new LoadoutOption('hooded_cloak', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-hood', 'Hooded Cloak', [
                Stat::Evasion->value => 10,
            ]),
            new LoadoutOption('crystal_wand', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-crystal-wand', 'Crystal Wand', [
                Stat::Focus->value => 5,
            ]),
            new LoadoutOption('vestments_of_renewal', LoadoutSlot::Gear, self::GEAR_LEVEL, 'ra-robe', 'Vestments of Renewal', [
                Stat::Recover->value => 3,
            ]),
        ];
    }
}
