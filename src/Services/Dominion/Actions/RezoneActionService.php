<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Calculators\Dominion\Actions\RezoningCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\ProtectionService;
use OpenDominion\Traits\DominionGuardsTrait;

class RezoneActionService
{
    use DominionGuardsTrait;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var ProtectionService */
    protected $protectionService;

    /** @var RezoningCalculator */
    protected $rezoningCalculator;

    /**
     * RezoneActionService constructor.
     */
    public function __construct()
    {
        $this->landCalculator = app(LandCalculator::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->protectionService = app(ProtectionService::class);
        $this->rezoningCalculator = app(RezoningCalculator::class);
    }

    /**
     * Does a rezone action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $remove Land to remove
     * @param array $add Land to add.
     * @return array
     * @throws GameException
     */
    public function rezone(Dominion $dominion, array $remove, array $add): array
    {
        $this->guardLockedDominion($dominion);

        // Level out rezoning going to the same type.
        foreach (array_intersect_key($remove, $add) as $key => $value) {
            $sub = min($value, $add[$key]);
            $remove[$key] -= $sub;
            $add[$key] -= $sub;
        }

        // Filter out empties.
        $remove = array_filter($remove);
        $add = array_filter($add);

        $totalLand = array_sum($remove);

        if (($totalLand <= 0) || $totalLand !== array_sum($add)) {
            throw new GameException('Re-zoning was not completed due to bad input.');
        }

        // Check if the requested amount of land is barren.
        foreach ($remove as $landType => $landToRemove) {

            if($landToRemove < 0) {
                throw new GameException('Re-zoning was not completed due to bad input.');
            }

            $landAvailable = $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType);
            if ($landToRemove > $landAvailable) {
                throw new GameException('You do not have enough barren land to re-zone ' . $landToRemove . ' ' . str_plural($landType, $landAvailable));
            }
        }

        $costPerAcre = $this->rezoningCalculator->getPlatinumCost($dominion);
        $platinumCost = $totalLand * $costPerAcre;

        if ($platinumCost > $dominion->resource_platinum) {
            throw new GameException("You do not have enough platinum to re-zone {$totalLand} acres of land.");
        }

        // Check for excessive DP reduction
        $defensiveMultiplier = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $defenseBeforeDestroy = $this->militaryCalculator->getDefensivePowerRaw($dominion);
        $defenseBeforeDestroy *= $defensiveMultiplier;

        // All fine, perform changes.
        $dominion->resource_platinum -= $platinumCost;
        $dominion->stat_total_platinum_spent_rezoning += $platinumCost;

        foreach ($remove as $landType => $amount) {
            $dominion->{'land_' . $landType} -= $amount;
        }
        foreach ($add as $landType => $amount) {
            $dominion->{'land_' . $landType} += $amount;
        }

        // Check for excessive DP reduction
        $defensiveMultiplier = $this->militaryCalculator->getDefensivePowerMultiplier($dominion);
        $defenseAfterDestroy = $this->militaryCalculator->getDefensivePowerRaw($dominion);
        $defenseAfterDestroy *= $defensiveMultiplier;
        $defenseReduced = $defenseBeforeDestroy - $defenseAfterDestroy;

        if ($defenseReduced > 0 && !$this->protectionService->isUnderProtection($dominion)) {
            $defenseReducedRecently = $this->militaryCalculator->getDefenseReducedRecently($dominion);
            if ((($defenseReduced + $defenseReducedRecently) / ($defenseBeforeDestroy + $defenseReducedRecently)) > 0.15) {
                throw new GameException('You cannot reduce your defense by more than 15% during a 24 hour period.');
            }
        }

        $dominion->save([
            'event' => HistoryService::EVENT_ACTION_REZONE,
            'defense_reduced' => $defenseReduced
        ]);

        return [
            'message' => sprintf(
                'Your land has been re-zoned at a cost of %s platinum.',
                number_format($platinumCost)
            ),
            'data' => [
                'platinumCost' => $platinumCost,
            ]
        ];
    }
}
