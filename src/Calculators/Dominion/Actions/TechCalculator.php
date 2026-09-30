<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Helpers\TechHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Tech;

class TechCalculator
{
    /** @var HeroCalculator */
    protected $heroCalculator;

    /** @var TechHelper */
    protected $techHelper;

    /**
     * TechCalculator constructor.
     */
    public function __construct(
        HeroCalculator $heroCalculator,
        TechHelper $techHelper
    )
    {
        $this->heroCalculator = $heroCalculator;
        $this->techHelper = $techHelper;
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

        $permanentTechCount = $dominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id === null;
        })->count();

        $techCost = (2.5 * $dominion->highest_land_achieved) + (50 * $permanentTechCount);

        return max(3750, round($techCost * $multiplier));
    }

    /**
     * Returns the number of techs the Dominion can unlock right now.
     *
     * Capped by the number of techs still available to research, so that a
     * fully teched Dominion is never told it has techs left to unlock.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getUnlockableTechCount(Dominion $dominion): int
    {
        $affordableTechCount = rfloor($dominion->resource_tech / $this->getTechCost($dominion));

        if ($affordableTechCount <= 0) {
            return 0;
        }

        return min($affordableTechCount, $this->getAvailableTechCount($dominion));
    }

    /**
     * Returns the number of techs the Dominion has not unlocked yet and meets
     * the prerequisites for, regardless of research points.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getAvailableTechCount(Dominion $dominion): int
    {
        $unlockedTechKeys = $dominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id === null;
        })->pluck('key')->all();

        return $this->techHelper->getTechs($dominion->round->tech_version)
            ->reject(function ($tech) use ($unlockedTechKeys) {
                return in_array($tech->key, $unlockedTechKeys);
            })
            ->filter(function ($tech) use ($dominion) {
                return $this->hasPrerequisites($dominion, $tech);
            })
            ->count();
    }

    /**
     * Determine if the Dominion meets the requirements to unlock a new tech.
     *
     * @param Dominion $dominion
     * @return bool
     */
    public function hasPrerequisites(Dominion $dominion, Tech $tech): bool
    {
        $unlockedTechs = $dominion->techs->filter(function ($tech) {
            return $tech->pivot->source_id === null;
        })->pluck('key')->all();

        return $tech->prerequisites == null || count(array_intersect($tech->prerequisites, $unlockedTechs)) != 0;
    }
}
