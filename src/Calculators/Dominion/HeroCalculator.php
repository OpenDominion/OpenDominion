<?php

namespace OpenDominion\Calculators\Dominion;

use Illuminate\Support\Collection;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Hero;
use OpenDominion\Helpers\HeroHelper;

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
     * Returns the Dominion's experience gain multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function geExperienceMultiplier(Dominion $dominion): float
    {
        $multiplier = 0;

        // Values (percentages)
        $xpPerShrine = 2;
        $xpPerShrineMax = 40;

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

        $heroPerk = $this->heroHelper->getTrades()[$dominion->hero->trade]['perk_type'];
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
    public function getTradeBonus(Hero $hero, string $perkType): float
    {
        $level = $this->getHeroLevel($hero);
        $coefficient = $this->getTradeCoefficient($perkType);

        if ($level == 0) {
            return 0;
        }

        return $coefficient * exp($level / 4);
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
        $bonusPerShrine = 2;
        $bonusPerShrineMax = 40;

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
        $xpLevels = $this->getExperienceLevels();

        return $xpLevels->filter(function($level) use ($hero) {
            return $level['xp'] <= $hero->experience;
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
                'xp' => 50,
            ],
            [
                'level' => 2,
                'xp' => 150,
            ],
            [
                'level' => 3,
                'xp' => 300,
            ],
            [
                'level' => 4,
                'xp' => 500,
            ],
            [
                'level' => 5,
                'xp' => 800,
            ],
            [
                'level' => 6,
                'xp' => 1200,
            ],
            [
                'level' => 7,
                'xp' => 1700,
            ],
            [
                'level' => 8,
                'xp' => 2300,
            ],
            [
                'level' => 9,
                'xp' => 3000,
            ],
            [
                'level' => 10,
                'xp' => 4000,
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

    public function getTradeDescription(Hero $hero)
    {
        $perkType = $this->heroHelper->getTrades()[$hero->trade]['perk_type'];
        $perkValue = $this->getTradeBonus($hero, $perkType);
        $helpString = vsprintf(
            $this->heroHelper->getTradeHelpString($hero->trade),
            number_format($perkValue, 4)
        );

        return $helpString;
    }
}
