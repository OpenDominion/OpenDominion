<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Models\HeroCombatant;
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
     * Returns the Dominion's experience gain.
     *
     * @param Dominion $dominion
     * @param int $value
     * @return float
     */
    public function getExperienceGain(Dominion $dominion, int $value, string $source): float
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
            $coefficient = 0.6;
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

        return $coefficient * $value * $this->getExperienceMultiplier($dominion);
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

        // TODO: Refactor this
        $maxUnlockLevel = 6;
        $heroLevel = min($this->getHeroLevel($hero), $maxUnlockLevel);
        $upgradeLevels = $hero->upgrades->where('type', '!=', 'directive')->pluck('level')->all();

        if ($heroLevel < 2) {
            $unlockLevels = [];
        } elseif ($heroLevel < 4) {
            $unlockLevels = [2];
        } elseif ($heroLevel < 6) {
            $unlockLevels = [2, 4];
        } else {
            $unlockLevels = range(2, $heroLevel, 2);
        }

        // Add doctrines
        if ($heroLevel > 0) {
            $unlockLevels[] = 1;
        }

        if ($hero->class === 'scion') {
            $unlockLevels[] = 0;
        }

        return count(array_diff($unlockLevels, $upgradeLevels));
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
            'health' => 60 + (5 * $level),
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
        $level = $this->getHeroLevel($hero);
        if ($hero->class_data !== null) {
            // Combat stats based on highest level class
            $level = max($level, collect($hero->class_data)->max('level'));
        }
        $combatStats = $this->getBaseCombatStats($level);

        foreach ($combatStats as $stat => $value) {
            $combatStats[$stat] += $hero->getPerkValue("combat_{$stat}");
        }

        return $combatStats;
    }

    public function getCombatStat(HeroCombatant $combatant, string $stat): int
    {
        $multiplier = 1;

        if (in_array('last_stand', $combatant->abilities ?? []) && $combatant->current_health <= 40) {
            $multiplier = 1.1;
        }

        if ($stat == 'attack') {
            // Enrage
            if (in_array('enrage', $combatant->abilities ?? []) && $combatant->current_health <= 40) {
                return round($combatant->attack * $multiplier) + 10;
            }
        }

        if ($stat == 'defense') {
            // Rally
            if (in_array('rally', $combatant->abilities ?? []) && $combatant->current_health <= 40) {
                return round($combatant->defense * $multiplier) + 5;
            }
            // Arcane Shield
            if (in_array('arcane_shield', $combatant->abilities ?? [])) {
                return round($combatant->defense * $multiplier) + 10;
            }
            // Weakened
            if (in_array('weakened', $combatant->abilities ?? [])) {
                return round($combatant->defense * $multiplier) - 15;
            }
            // Undying Legion
            if (in_array('undying_legion', $combatant->abilities ?? [])) {
                $livingMinions = $combatant->battle->combatants
                    ->where('id', '!=', $combatant->id)
                    ->where('hero_id', null)
                    ->where('current_health', '>', 0)
                    ->count();
                if ($livingMinions > 0) {
                    return 999;
                }
            }
        }

        if ($stat == 'recover') {
            // Mending
            if (in_array('mending', $combatant->abilities ?? []) && $combatant->has_focus) {
                return round($combatant->recover * $multiplier) + round($combatant->focus * $multiplier);
            }
        }

        if ($stat == 'counter') {
            // Retribution
            if (in_array('retribution', $combatant->abilities ?? [])) {
                return round($combatant->counter * $multiplier) + 15;
            }
        }

        return round($combatant->{$stat} * $multiplier);
    }

    public function calculateCombatDamage(HeroCombatant $combatant, HeroCombatant $target, array $actionDef, bool $counterAttack = false): int
    {
        $baseDamage = $this->getCombatStat($combatant, 'attack');
        $baseDefense = $this->getCombatStat($target, 'defense');
        $defendModifier = $actionDef['attributes']['defend'] ?? 0;
        $bonusDamage = $actionDef['attributes']['bonus_damage'] ?? 0;

        if ($combatant->current_action == 'counter') {
            $baseDamage += $this->getCombatStat($combatant, 'counter');
        } elseif ($combatant->has_focus) {
            $baseDamage += $this->getCombatStat($combatant, 'focus');
        }

        // Add bonus damage
        $baseDamage += $bonusDamage;

        if ($target->current_action == 'recover') {
            $baseDefense -= 5;
        }

        if ($target->current_action == 'defend') {
            $baseDefense *= 2;
            $baseDefense += $defendModifier;
        }

        $damage = max(0, $baseDamage - $baseDefense);

        return round($damage);
    }

    public function calculateCombatEvade(HeroCombatant $target, array $actionDef): bool
    {
        $evaded = $actionDef['attributes']['evade'] ?? null;
        if ($evaded !== null) {
            return $evaded;
        }

        return mt_rand(0, 100) < $this->getCombatStat($target, 'evasion');
    }

    public function calculateCombatHeal(HeroCombatant $combatant): int
    {
        return $this->getCombatStat($combatant, 'recover');
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
                return (int) $changeDate->diffInHours(now()->startOfHour(), absolute: true);
            }
        }

        return 0;
    }
}
