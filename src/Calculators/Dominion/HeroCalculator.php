<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\HeroCombat\Content\Loadouts\HeroClassLoadouts;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutOption;
use OpenDominion\HeroCombat\Content\Loadouts\LoadoutSlot;
use OpenDominion\HeroCombat\Engine\Stats\Stat;
use OpenDominion\HeroCombat\Registry\CombatRegistry;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroUpgrade;

class HeroCalculator
{
    /**
     * @var float The percentage of each inactive class bonus that is lost
     */
    public const INACTIVE_CLASS_PENALTY = 0.5;

    /**
     * @var int Hours required between class changes
     */
    public const CLASS_CHANGE_COOLDOWN_HOURS = 96;

    /**
     * @var int Maximum XP lost from the current class when changing class
     */
    public const CLASS_CHANGE_XP_PENALTY = 500;

    /**
     * @var int Daily cap on raw XP gained from spy + magic operations combined.
     *          Measured pre-getExperienceMultiplier so shrine/racial/wonder bonuses
     *          do not accelerate hitting the cap.
     */
    public const DAILY_OPS_XP_CAP = 120;

    /** @var HeroHelper */
    protected $heroHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /**
     * HeroHelper constructor.
     */
    public function __construct()
    {
        $this->heroHelper = app(HeroHelper::class);
        $this->landCalculator = app(LandCalculator::class);
    }

    /**
     * Returns the Dominion's raw experience gain (source coefficient + hero perks only,
     * without the shrine/racial/wonder multiplier from getExperienceMultiplier).
     */
    public function getRawExperienceGain(Dominion $dominion, int $value, string $source): float
    {
        $landGainBonus = $dominion->hero->getPerkMultiplier('xp_from_land_gain_bonus');
        $opsBonus = $dominion->hero->getPerkMultiplier('xp_from_ops_bonus');
        $opsPenalty = $dominion->hero->getPerkMultiplier('xp_from_ops_penalty');

        if ($source == 'invasion') {
            $coefficient = 1;
            if ($landGainBonus != 0) {
                $coefficient *= (1 + $landGainBonus);
            }
        } elseif ($source == 'exploration') {
            $coefficient = 0.8;
            if ($landGainBonus != 0) {
                $coefficient *= (1 + $landGainBonus);
            }
        } elseif ($source == 'spy') {
            $coefficient = 0.5;
            if ($opsBonus != 0) {
                $coefficient *= (1 + $opsBonus);
            }
            if ($opsPenalty != 0) {
                $coefficient *= (1 - $opsPenalty);
            }
        } elseif ($source == 'magic') {
            $coefficient = 1;
            if ($opsBonus != 0) {
                $coefficient *= (1 + $opsBonus);
            }
            if ($opsPenalty != 0) {
                $coefficient *= (1 - $opsPenalty);
            }
        } else {
            $coefficient = 1;
        }

        return $coefficient * $value;
    }

    /**
     * Returns the Dominion's experience gain (raw value amplified by the
     * shrine/racial/wonder experience multiplier).
     */
    public function getExperienceGain(Dominion $dominion, int $value, string $source): float
    {
        return $this->getRawExperienceGain($dominion, $value, $source) * $this->getExperienceMultiplier($dominion);
    }

    /**
     * Returns the Dominion's experience gain multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getExperienceMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values (percentages)
        $xpPerShrine = 40;
        $xpPerShrineMax = 200;

        $multiplier += min(
            ($xpPerShrine * $dominion->building_shrine / $this->landCalculator->getTotalLand($dominion)),
            ($xpPerShrineMax / 100)
        );

        // Racial
        $multiplier += $dominion->race->getPerkMultiplier('hero_experience');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('hero_experience');

        // Round Perks
        $multiplier += $dominion->getRoundPerkMultiplier('hero_experience');

        return $multiplier;
    }

    /**
     * Returns the Dominion's passive hero perk multiplier.
     *
     * @param Dominion $dominion
     * @param string $perkType
     * @return float
     */
    public function getHeroPerkMultiplier(Dominion $dominion, string $perkType): float
    {
        if (!$dominion->hero) {
            return 0;
        }

        $bonus = $this->getPassiveBonus($dominion->hero, $perkType) / 100;
        $bonus *= $this->getPassiveBonusMultiplier($dominion);

        return $bonus;
    }

    /**
     * Returns the passive hero perk bonus.
     *
     * @param Hero $hero
     * @param string $perkType
     * @return float
     */
    public function getPassiveBonus(Hero $hero, string $perkType): float
    {
        $isCurrentClass = false;

        $classes = collect($hero->class_data);
        $class = $classes->where('perk_type', $perkType)->first();
        if ($class === null) {
            $isCurrentClass = $this->heroHelper->getPassivePerkType($hero->class) == $perkType;
            if (!$isCurrentClass) {
                return 0;
            }
        }

        $multiplier = 1;
        if ($isCurrentClass || $class['key'] == $hero->class) {
            // Active Class
            $level = $this->getHeroLevel($hero);
        } else {
            // Inactive Class
            $level = $this->getExperienceLevel($class['experience']);
            $multiplier = (1 - $this::INACTIVE_CLASS_PENALTY);
        }

        return $this->calculatePassiveBonus($perkType, $level) * $multiplier;
    }

    /**
     * Calculates the passive hero perk by level.
     *
     * @param string $perkType
     * @param int $level
     * @return float
     */
    public function calculatePassiveBonus(string $perkType, int $level)
    {
        if ($level == 0) {
            return 0;
        }

        $coefficient = $this->getPassiveCoefficient($perkType);

        return $coefficient * $level;
    }

    /**
     * Returns the Dominion's passive hero perk multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getPassiveBonusMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values (percentages)
        $bonusPerShrine = 40;
        $bonusPerShrineMax = 200;

        $multiplier += min(
            ($bonusPerShrine * $dominion->building_shrine / $this->landCalculator->getTotalLand($dominion)),
            ($bonusPerShrineMax / 100)
        );

        // Racial
        $multiplier += $dominion->race->getPerkMultiplier('hero_bonus');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('hero_bonus');

        return $multiplier;
    }

    /**
     * Returns the current level of the hero.
     *
     * @param Hero $hero
     * @return float
     */
    public function getHeroLevel(Hero $hero): float
    {
        return $this->getExperienceLevel($hero->experience);
    }

    /**
     * Returns the hero's level in a class: the hero level for the active class, otherwise the
     * level of the experience stored for that class (0 if never played).
     *
     * @param Hero $hero
     * @param string $class
     * @return int
     */
    public function getClassLevel(Hero $hero, string $class): int
    {
        if ($hero->class === $class) {
            return (int) $this->getHeroLevel($hero);
        }

        $classEntry = collect($hero->class_data ?? [])->firstWhere('key', $class);

        return (int) $this->getExperienceLevel($classEntry['experience'] ?? 0);
    }

    /**
     * Returns true if the hero currently has or has previously reached level 1+ in the given class.
     *
     * @param Hero $hero
     * @param string $class
     * @return bool
     */
    public function heroHasClass(Hero $hero, string $class): bool
    {
        if ($hero->class === $class) {
            return true;
        }
        foreach (collect($hero->class_data ?? []) as $classEntry) {
            if (($classEntry['key'] ?? null) === $class) {
                return $this->getExperienceLevel($classEntry['experience'] ?? 0) >= 1;
            }
        }
        return false;
    }

    /**
     * Returns the level by experience total.
     *
     * @param float $experience
     * @return float
     */
    public function getExperienceLevel(float $experience): float
    {
        $xpLevels = $this->getExperienceLevels();

        return $xpLevels->filter(function ($level) use ($experience) {
            return $level['xp'] <= $experience;
        })->max('level');
    }

    /**
     * Returns the experience required for a specific level.
     *
     * @param int $level
     * @return int
     */
    protected function getXpForLevel(int $level): int
    {
        $xpLevels = $this->getExperienceLevels();
        $levelData = $xpLevels->firstWhere('level', $level);
        return $levelData ? $levelData['xp'] : 0;
    }

    /**
     * Returns the minimum experience required for the hero's current level.
     *
     * @param Hero $hero
     * @return int
     */
    public function getCurrentLevelXP(Hero $hero): int
    {
        $currentLevel = $this->getHeroLevel($hero);
        return $this->getXpForLevel($currentLevel);
    }

    /**
     * Returns the experience required to reach the next level.
     *
     * @param Hero $hero
     * @return int
     */
    public function getNextLevelXP(Hero $hero): int
    {
        $currentLevel = $this->getHeroLevel($hero);
        $nextLevel = $currentLevel + 1;
        return $this->getXpForLevel($nextLevel) ?: 99999;
    }

    /**
     * Returns the experience required for each level.
     *
     * @return Collection
     */
    public function getExperienceLevels(): Collection
    {
        return collect([
            [
                'level' => 0,
                'xp' => 0,
            ],
            [
                'level' => 1,
                'xp' => 200,
            ],
            [
                'level' => 2,
                'xp' => 700,
            ],
            [
                'level' => 3,
                'xp' => 1200,
            ],
            [
                'level' => 4,
                'xp' => 1750,
            ],
            [
                'level' => 5,
                'xp' => 2300,
            ],
            [
                'level' => 6,
                'xp' => 2900,
            ],
            [
                'level' => 7,
                'xp' => 3500,
            ],
            [
                'level' => 8,
                'xp' => 4250,
            ],
            [
                'level' => 9,
                'xp' => 5000,
            ],
            [
                'level' => 10,
                'xp' => 6000,
            ],
            [
                'level' => 11,
                'xp' => 7500,
            ],
            [
                'level' => 12,
                'xp' => 10000,
            ],
        ]);
    }

    /**
     * Returns the passive hero perk coefficient.
     *
     * @param string $perkType
     * @return float
     */
    protected function getPassiveCoefficient(string $perkType): float
    {
        $classes = $this->heroHelper->getClasses()->keyBy('perk_type');

        if (isset($classes[$perkType])) {
            return $classes[$perkType]['coefficient'];
        }

        return 0;
    }

    /**
     * Returns the HTML description of the passive hero perk bonus.
     *
     * @param Hero $hero
     * @return float
     */
    public function getPassiveDescription(Hero $hero, string|null $perkType = null): string
    {
        if ($perkType === null) {
            $perkType = $this->heroHelper->getPassivePerkType($hero->class);
        }
        $perkValue = $this->getHeroPerkMultiplier($hero->dominion, $perkType);
        $helpString = sprintf(
            $this->heroHelper->getPassiveHelpString($perkType),
            number_format($perkValue * 100, 2)
        );

        return $helpString;
    }

    public function getUnlockableUpgradeCount(Hero|null $hero): int
    {
        if ($hero === null) {
            return 0;
        }

        $heroLevel = $this->getHeroLevel($hero);
        $openLevels = array_filter(
            $this->getUpgradeSlots($hero),
            fn ($upgrade, $level) => $upgrade === null && $level <= $heroLevel,
            ARRAY_FILTER_USE_BOTH
        );

        return count($openLevels);
    }

    /**
     * Upgrade slots by level: every level with at least one active, slot-consuming upgrade
     * available to the hero's class, mapped to the upgrade chosen there (or null).
     * Directives are free picks and never take a slot.
     *
     * @return array<int, HeroUpgrade|null>
     */
    public function getUpgradeSlots(Hero $hero): array
    {
        $slotLevels = $this->heroHelper->getHeroUpgradesByClass($hero->class)
            ->where('active', true)
            ->where('type', '!=', 'directive')
            ->pluck('level')
            ->unique()
            ->sort()
            ->values();

        $chosen = $hero->upgrades->where('type', '!=', 'directive')->keyBy('level');

        $slots = [];
        foreach ($slotLevels as $level) {
            $slots[$level] = $chosen->get($level);
        }

        return $slots;
    }

    public function canUnlockUpgrade(Hero $hero, HeroUpgrade $upgrade): bool
    {
        if (count($upgrade->classes) && !in_array($hero->class, $upgrade->classes)) {
            return false;
        }

        $heroLevel = $this->getHeroLevel($hero);
        $levelsUnlocked = $hero->upgrades->where('type', '!=', 'directive')->pluck('level')->all();

        return $heroLevel >= $upgrade->level && !in_array($upgrade->level, $levelsUnlocked);
    }

    public function getBaseCombatStats(int $level = 0): array
    {
        return [
            'health' => 80 + (2 * $level),
            'attack' => 40,
            'defense' => 20,
            'evasion' => 10,
            'focus' => 10,
            'counter' => 10,
            'recover' => 20,
        ];
    }

    public function getHeroCombatStats(Hero $hero): array
    {
        $combatStats = $this->getBaseCombatStats($this->getCombatLevel($hero));

        foreach ($this->getLoadoutStatBonuses($hero) as $stat => $bonus) {
            $combatStats[$stat] += $bonus;
        }

        return $combatStats;
    }

    /**
     * The hero's level for combat purposes: its highest level in any class, so changing class
     * costs neither combat stats nor loadout options.
     */
    public function getCombatLevel(Hero $hero): int
    {
        return (int) max($this->getHeroLevel($hero), collect($hero->class_data ?? [])->max('level') ?? 0);
    }

    /**
     * Everything the hero can currently equip. This is the only place that decides access,
     * so sources other than levels belong here.
     *
     * @return array<string, LoadoutOption> keyed by option key
     */
    public function getAvailableLoadoutOptions(Hero $hero, ?LoadoutSlot $slot = null): array
    {
        $combatLevel = $this->getCombatLevel($hero);
        $loadouts = app(HeroClassLoadouts::class);

        return array_filter(
            app(CombatRegistry::class)->loadoutOptions($slot),
            function (LoadoutOption $option) use ($hero, $combatLevel, $loadouts) {
                if (!$this->isLoadoutSlotEnabled($option->slot)) {
                    return false;
                }

                $requiredClass = $loadouts->classFor($option->key);
                $level = $requiredClass === null ? $combatLevel : $this->getClassLevel($hero, $requiredClass);

                return $level >= $option->level;
            }
        );
    }

    /**
     * How many options of a kind the hero can bring at their combat level.
     */
    public function getLoadoutSlotCapacity(Hero $hero, LoadoutSlot $slot): int
    {
        if (!$this->isLoadoutSlotEnabled($slot)) {
            return 0;
        }

        return $slot->capacityAt($this->getCombatLevel($hero));
    }

    /**
     * Whether heroes can equip this kind of option yet (config `heroes.loadout_slots`).
     * Disabled slots are shown as coming soon and nothing in them reaches a battle.
     */
    public function isLoadoutSlotEnabled(LoadoutSlot $slot): bool
    {
        return in_array($slot->value, config('heroes.loadout_slots', []), true);
    }

    /**
     * Whether gear leaves a hero's evasion at 0 or more. Evasion can't go negative, so gear
     * that each costs evasion (Greatsword and Plate Armor) can't be worn together.
     *
     * @param LoadoutOption[] $gear
     */
    public function gearKeepsEvasion(array $gear): bool
    {
        $evasion = $this->getBaseCombatStats()['evasion'];

        foreach ($gear as $option) {
            $evasion += $option->stats[Stat::Evasion->value] ?? 0;
        }

        return $evasion >= 0;
    }

    /**
     * The options the hero has equipped, by slot value. Anything stored that the hero can no
     * longer equip is left out, as is anything beyond a slot's capacity, and gear that would
     * take evasion below 0.
     *
     * @return array<string, LoadoutOption[]>
     */
    public function getLoadout(Hero $hero): array
    {
        $loadout = [];

        foreach (LoadoutSlot::cases() as $slot) {
            $keys = array_unique(array_filter((array) ($hero->loadout[$slot->value] ?? [])));
            $available = $this->getAvailableLoadoutOptions($hero, $slot);
            $equipped = array_values(array_intersect_key($available, array_flip($keys)));

            $equipped = array_slice($equipped, 0, $this->getLoadoutSlotCapacity($hero, $slot));

            if ($slot === LoadoutSlot::Gear) {
                $equipped = array_reduce($equipped, fn (array $kept, LoadoutOption $option) => $this->gearKeepsEvasion([...$kept, $option]) ? [...$kept, $option] : $kept, []);
            }

            $loadout[$slot->value] = $equipped;
        }

        return $loadout;
    }

    /**
     * Combat stat changes from the hero's equipped gear.
     *
     * @return array<string, int> keyed by Stat value
     */
    public function getLoadoutStatBonuses(Hero $hero): array
    {
        $bonuses = [];

        foreach ($this->getLoadout($hero) as $options) {
            foreach ($options as $option) {
                foreach ($option->stats as $stat => $value) {
                    $bonuses[$stat] = ($bonuses[$stat] ?? 0) + $value;
                }
            }
        }

        return $bonuses;
    }

    /**
     * Loadout slots sitting empty that the hero could fill right now.
     */
    public function getEmptyLoadoutSlotCount(Hero|null $hero): int
    {
        if ($hero === null) {
            return 0;
        }

        $loadout = $this->getLoadout($hero);
        $count = 0;

        foreach (LoadoutSlot::cases() as $slot) {
            $equipped = count($loadout[$slot->value]);
            $unequipped = count($this->getAvailableLoadoutOptions($hero, $slot)) - $equipped;

            $count += min($this->getLoadoutSlotCapacity($hero, $slot) - $equipped, $unequipped);
        }

        return $count;
    }

    /**
     * Slot values with options the hero can equip now but couldn't at the given experience,
     * or with room for more of them (the second gear slot). Compares options rather than
     * levels because class abilities depend on class level.
     *
     * @return string[]
     */
    public function getNewLoadoutSlots(Hero $hero, float $previousExperience): array
    {
        $previousHero = clone $hero;
        $previousHero->experience = $previousExperience;

        $newOptions = array_diff_key(
            $this->getAvailableLoadoutOptions($hero),
            $this->getAvailableLoadoutOptions($previousHero)
        );
        $newSlots = array_map(fn (LoadoutOption $option) => $option->slot, $newOptions);

        foreach (LoadoutSlot::cases() as $slot) {
            if ($this->getLoadoutSlotCapacity($hero, $slot) > $this->getLoadoutSlotCapacity($previousHero, $slot)) {
                $newSlots[] = $slot;
            }
        }

        return array_values(array_map(
            fn (LoadoutSlot $slot) => $slot->value,
            array_filter(LoadoutSlot::cases(), fn (LoadoutSlot $slot) => in_array($slot, $newSlots, true))
        ));
    }

    public function calculateRatingChange(float $currentRating, float $opponentRating, float $result): int
    {
        $k = 32;
        $expected = 1 / (1 + pow(10, ($opponentRating - $currentRating) / 480));
        $newRating = $currentRating + $k * ($result - $expected);

        return round($newRating);
    }

    /**
     * Check if the hero can change class (not on cooldown)
     *
     * @param Hero $hero
     * @return bool
     */
    public function canChangeClass(Hero $hero): bool
    {
        if ($hero->last_class_change_at === null) {
            return true;
        }

        if (!$hero->dominion->round->hasStarted()) {
            return true;
        }

        return $this->hoursUntilClassChange($hero) == 0;
    }

    /**
     * Get hours until the hero can change class again
     *
     * @param Hero $hero
     * @return int
     */
    public function hoursUntilClassChange(Hero $hero): int
    {
        if ($hero->last_class_change_at !== null) {
            $changeDate = $hero->last_class_change_at->copy()->addHours(self::CLASS_CHANGE_COOLDOWN_HOURS);

            if ($changeDate > now()->startOfHour()) {
                return (int) now()->startOfHour()->diffInHours($changeDate);
            }
        }

        return 0;
    }
}
