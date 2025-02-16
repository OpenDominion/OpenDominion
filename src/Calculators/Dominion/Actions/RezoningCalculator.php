<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Models\Dominion;

class RezoningCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var SpellCalculator */
    protected $spellCalculator;

    /**
     * RezoningCalculator constructor.
     *
     * @param LandCalculator $landCalculator
     */
    public function __construct(
        LandCalculator $landCalculator,
        SpellCalculator $spellCalculator
    )
    {
        $this->landCalculator = $landCalculator;
        $this->spellCalculator = $spellCalculator;
    }

    /**
     * Returns the Dominion's rezoning platinum cost (per acre of land).
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getPlatinumCost(Dominion $dominion): int
    {
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        if ($dominion->stat_total_land_lost >= $dominion->stat_total_land_conquered) {
            $conqueredLand = 0;
            $exploredLand = $totalLand - 250 + max(0, $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost);
        } else {
            $conqueredLand = $dominion->stat_total_land_conquered - $dominion->stat_total_land_lost;
            $exploredLand = $totalLand - 250 - $conqueredLand;
        }

        $platinum = 250 + (0.6 * $exploredLand) + (0.2 * $conqueredLand);

        $multiplier = $this->getCostMultiplier($dominion);

        return round($platinum * $multiplier);
    }

    /**
     * Returns the maximum number of acres of land a Dominion can rezone.
     *
     * @param Dominion $dominion
     * @return int
     */
    public function getMaxAfford(Dominion $dominion): int
    {
        return min(
            rfloor($dominion->resource_platinum / $this->getPlatinumCost($dominion)),
            $this->landCalculator->getTotalBarrenLand($dominion)
        );
    }

    /**
     * Returns the Dominion's rezoning cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1;

        // Values (percentages)
        $factoryReduction = 5;
        $factoryReductionMax = 50;

        // Factories
        $multiplier -= min(
            (($dominion->building_factory / $this->landCalculator->getTotalLand($dominion)) * $factoryReduction),
            ($factoryReductionMax / 100)
        );

        // Racial Bonus
        $multiplier += $dominion->race->getPerkMultiplier('rezone_cost');

        // Techs
        $multiplier += $dominion->getTechPerkMultiplier('rezone_cost');

        // Wonders
        $multiplier += $dominion->getWonderPerkMultiplier('rezone_cost');

        // Spells
        $multiplier += $this->spellCalculator->resolveSpellPerk($dominion, 'rezone_cost') / 100;

        return $multiplier;
    }
}
