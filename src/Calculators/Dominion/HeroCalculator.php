<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Helpers\HeroHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;

class HeroCalculator
{
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
    public function getExperienceGain(Dominion $dominion, int $xpGain): float
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
        $xpPerShrine = 2;
        $xpPerShrineMax = 20;

        $multiplier += min(
            ($xpPerShrine * $dominion->building_shrine / $this->landCalculator->getTotalLand($dominion)),
            ($xpPerShrineMax / 100)
        );

        return $multiplier;
    }

    /**
     * Returns the Dominion's trade multiplier.
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

        $heroPerk = $this->heroHelper->getTradePerkType($dominion->hero->trade);
        if ($heroPerk !== $perkType) {
            return 0;
        }

        $tradeBonus = $this->getTradeBonus($dominion->hero, $perkType) / 100;
        $tradeBonus *= (1 + $this->getTradeBonusMultiplier($dominion));

        return $tradeBonus;
    }

    /**
     * Returns the Hero's trade bonus.
     *
     * @param Hero $hero
     * @param string $perkType
     * @return float
     */
    public function getTradeBonus(Hero $hero, ?string $perkType = null): float
    {
        if (!$perkType) {
            $perkType = $this->heroHelper->getTradePerkType($hero->trade);
        }
        $level = $this->getHeroLevel($hero);

        return $this->calculateTradeBonus($perkType, $level);
    }

    /**
     * Calculates a level trade bonus.
     *
     * @param Hero $hero
     * @param string $perkType
     * @return float
     */
    public function calculateTradeBonus(string $perkType, int $level)
    {
        if ($level == 0) {
            return 0;
        }

        $coefficient = $this->getTradeCoefficient($perkType);

        return $coefficient * $level;
    }

    /**
     * Returns the Dominion's trade bonus multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getTradeBonusMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $bonusPerShrine = 50;
        $bonusPerShrineMax = 500;

        $multiplier += min(
            ($bonusPerShrine * $dominion->building_shrine / $this->landCalculator->getTotalLand($dominion)),
            ($bonusPerShrineMax / 100)
        );

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
     * Returns the experience required to reach the next level.
     *
     * @param Hero $hero
     * @return int
     */
    public function getNextLevelXP(Hero $hero): int
    {
        $level = $this->getHeroLevel($hero);
        $xpLevels = $this->getExperienceLevels();
        $nextLevel = $xpLevels->firstWhere('level', $level + 1);
        return $nextLevel['xp'] ?: 99999;
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
     * Returns the trade coefficient.
     *
     * @param string $perkType
     * @return float
     */
    protected function getTradeCoefficient(string $perkType): float
    {
        $trades = $this->heroHelper->getTrades()->keyBy('perk_type');

        if (isset($trades[$perkType])) {
            return $trades[$perkType]['coefficient'];
        }

        return 0;
    }

    /**
     * Returns the HTML description of the trade bonus.
     *
     * @param string $perkType
     * @return float
     */
    public function getTradeDescription(Hero $hero): string
    {
        $perkType = $this->heroHelper->getTrades()[$hero->trade]['perk_type'];
        $perkValue = $this->getTradeBonus($hero, $perkType);
        $helpString = vsprintf(
            $this->heroHelper->getTradeHelpString($hero->trade),
            number_format($perkValue, 2)
        );

        return $helpString;
    }
}
