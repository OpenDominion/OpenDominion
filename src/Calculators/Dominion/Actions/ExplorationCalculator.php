<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\GuardMembershipService;

class ExplorationCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var GuardMembershipService */
    protected $guardMembershipService;

    /**
     * ExplorationCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     * @param GuardMembershipService $guardMembershipService
     */
    public function __construct(
        LandCalculator $landCalculator,
        GuardMembershipService $guardMembershipService)
    {
        $this->landCalculator = $landCalculator;
        $this->guardMembershipService = $guardMembershipService;
    }

    /**
     * Returns the Dominion's exploration platinum cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        $platinum = 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalLand < 300) {
            $platinum += -(3 * (300 - $totalLand));
        } else {
            $exponent = ($totalLand ** 0.0185) / 1.05;
            $exponent = clamp($exponent, 1.09, 1.121);
            $platinum += (3 * (($totalLand - 300) ** $exponent));
        }

        $platinum += 1000;

        // Techs
        $platinum *= (1 - $dominion->getTechPerkMultiplier('explore_platinum_cost'));

        // Elite Guard Tax
        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            $platinum *= 1.25;
        }

        return round($platinum);
    }

    /**
     * Returns the Dominion's exploration draftee cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getDrafteeCost(Dominion $dominion): int
    {
        $draftees = 0;
        $totalLand = $this->landCalculator->getTotalLand($dominion);

        if ($totalLand < 300) {
            $draftees = -(300 / $totalLand);
        } else {
            $draftees += (0.003 * (($totalLand - 300) ** 1.07));
        }

        $draftees += 5;

        if($totalLand >= 4000) {
            $draftees *= 1.25;
        }

        // Techs
        $draftees += $dominion->getTechPerkMultiplier('explore_draftee_cost');
        # Minimum dratee cost is 3
        if ($draftees < 3) {
            $draftes = 3;
        }

        return round($draftees);
    }

    /**
     * Returns the maximum number of acres of land a Dominion can afford to
     * explore.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        return min(
            floor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            floor($dominion->military_draftees / $this->getDrafteeCost($dominion))
        );
    }

    /**
     * Returns the morale drop after exploring for $amount of acres of land.
     *
     * @param int $amount
     * @return int
     * @todo Does this really belong here? Maybe it should go in a helper, since it isn't dependent on a Dominion instance
     */
    public function getMoraleDrop($amount): int
    {
        return floor(($amount + 2) / 3);
    }
}
