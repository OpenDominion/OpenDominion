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
    protected const INACTIVE_CLASS_PENALTY = 0.5;

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
     * @param int $xpGain
     * @return float
     */
    public function getExperienceGain(Dominion $dominion, float $xpGain): float
    {
        return $xpGain * $this->getExperienceMultiplier($dominion);
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
        $xpPerShrine = 25;
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
        $bonusPerShrine = 25;
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
                'xp' => 100,
            ],
            [
                'level' => 2,
                'xp' => 300,
            ],
            [
                'level' => 3,
                'xp' => 600,
            ],
            [
                'level' => 4,
                'xp' => 1000,
            ],
            [
                'level' => 5,
                'xp' => 1500,
            ],
            [
                'level' => 6,
                'xp' => 2250,
            ],
            [
                'level' => 7,
                'xp' => 3000,
            ],
            [
                'level' => 8,
                'xp' => 3750,
            ],
            [
                'level' => 9,
                'xp' => 4750,
            ],
            [
                'level' => 10,
                'xp' => 6000,
            ],
            [
                'level' => 11,
                'xp' => 7750,
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
    public function getPassiveDescription(Hero $hero, ?string $perkType = null): string
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

    public function getUnlockableUpgradeCount(?Hero $hero): int
    {
        if ($hero === null) {
            return 0;
        }

        $maxUnlockLevel = 6;
        $heroLevel = min($this->getHeroLevel($hero), $maxUnlockLevel);
        $upgradeLevels = $hero->upgrades->where('type', '!=', 'directive')->pluck('level')->all();

        if ($heroLevel < 2) {
            $evenLevels = [];
        } elseif ($heroLevel < 4) {
            $evenLevels = [2];
        } elseif ($heroLevel < 6) {
            $evenLevels = [2, 4];
        } else {
            $evenLevels = range(2, $heroLevel, 2);
        }

        if ($hero->class === 'scion') {
            $evenLevels[] = 0;
        }

        return count(array_diff($evenLevels, $upgradeLevels));
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
            'health' => 80 + (5 * $level),
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
        $combatStats = $this->getBaseCombatStats($level);

        foreach ($combatStats as $stat => $value) {
            $combatStats[$stat] += $hero->getPerkValue("combat_{$stat}");
        }

        return $combatStats;
    }

    public function calculateCombatDamage(HeroCombatant $combatant, HeroCombatant $target, bool $counterAttack = false): int
    {
        $baseDamage = $combatant->attack;
        $baseDefense = $target->defense;

        if ($counterAttack) {
            $baseDamage += $combatant->counter;
        } elseif ($combatant->has_focus) {
            $baseDamage += $combatant->focus;
        }

        if ($target->current_action == 'recover') {
            $baseDefense -= 5;
        }

        if ($target->current_action == 'defend') {
            $baseDefense *= 2;
        }

        $damage = max(0, $baseDamage - $baseDefense);

        return round($damage);
    }

    public function calculateCombatEvade(HeroCombatant $target): bool
    {
        if ($target->current_action == 'recover') {
            return false;
        }
        return mt_rand(0, 100) < $target->evasion;
    }

    public function calculateCombatHeal(HeroCombatant $combatant): int
    {
        return $combatant->recover;
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
                return $changeDate->diffInHours(now()->startOfHour());
            }
        }

        return 0;
    }
}
