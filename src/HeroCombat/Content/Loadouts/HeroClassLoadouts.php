<?php

namespace OpenDominion\HeroCombat\Content\Loadouts;

/**
 * Combat abilities and passives each hero class brings to battle.
 */
class HeroClassLoadouts
{
    /**
     * Class abilities are not granted in battle yet; flip this to enable them.
     */
    public const ENABLED = false;

    public const BASIC_ABILITIES = ['attack', 'defend', 'focus', 'counter', 'recover'];

    /**
     * Abilities that replace a basic ability when granted.
     *
     * @var array<string, string>
     */
    public const REPLACES = [
        'fortify' => 'defend',
    ];

    /**
     * @return array<string, array{abilities: string[], passives: string[]}>
     */
    public function all(): array
    {
        return [
            'alchemist' => ['abilities' => ['volatile_mixture'], 'passives' => []],
            'architect' => ['abilities' => ['fortify'], 'passives' => []],
            'blacksmith' => ['abilities' => ['forge'], 'passives' => []],
            'engineer' => ['abilities' => ['tactical_awareness'], 'passives' => []],
            'farmer' => ['abilities' => [], 'passives' => ['hardiness']],
            'healer' => ['abilities' => [], 'passives' => ['mending']],
            'infiltrator' => ['abilities' => ['shadow_strike'], 'passives' => []],
            'sorcerer' => ['abilities' => [], 'passives' => ['channeling']],
            'scholar' => ['abilities' => ['combat_analysis'], 'passives' => []],
            'scion' => ['abilities' => [], 'passives' => ['last_stand']],
        ];
    }

    /**
     * Active abilities for a hero with the given class.
     *
     * @return string[]
     */
    public function abilitiesFor(?string $class, bool $enabled = self::ENABLED): array
    {
        $abilities = self::BASIC_ABILITIES;

        if ($enabled && $class !== null) {
            $abilities = $this->withGrants($abilities, $this->all()[$class]['abilities'] ?? []);
        }

        return $abilities;
    }

    /**
     * Innate passives for a hero with the given class.
     *
     * @return string[]
     */
    public function passivesFor(?string $class, bool $enabled = self::ENABLED): array
    {
        if (!$enabled || $class === null) {
            return [];
        }

        return $this->all()[$class]['passives'] ?? [];
    }

    /**
     * Adds abilities, removing any basic ability they replace.
     *
     * @param string[] $abilities
     * @param string[] $grants
     * @return string[]
     */
    public function withGrants(array $abilities, array $grants): array
    {
        foreach ($grants as $grant) {
            if (isset(self::REPLACES[$grant])) {
                $abilities = array_values(array_filter($abilities, fn ($a) => $a !== self::REPLACES[$grant]));
            }
            if (!in_array($grant, $abilities, true)) {
                $abilities[] = $grant;
            }
        }

        return $abilities;
    }
}
