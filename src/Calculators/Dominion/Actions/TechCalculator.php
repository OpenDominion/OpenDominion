<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Tech;

class TechCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /**
     * TechCalculator constructor.
     */
    public function __construct(
        HeroCalculator $heroCalculator
    )
    {
        $this->heroCalculator = $heroCalculator;
    }

    /**
     * Returns the Dominion's current research point cost to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getTechCost(Dominion $dominion): int
    {
        $multiplier = 1;

        // Racial
        $multiplier += $dominion->race->getPerkMultiplier('tech_cost');

        // Heroes
        $multiplier += $this->heroCalculator->getHeroPerkMultiplier($dominion, 'tech_cost');

        $techCost = (2.5 * $dominion->highest_land_achieved) + (50 * $dominion->techs->count());

        return max(3750, round($techCost * $multiplier));
    }

    /**
     * Determine if the Dominion meets the requirements to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasPrerequisites(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->pluck('key')->all();

        return $tech->prerequisites == null || count(array_intersect($tech->prerequisites, $unlockedTechs)) != 0;
    }
}
