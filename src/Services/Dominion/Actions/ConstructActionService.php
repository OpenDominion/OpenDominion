<?php

namespace OpenDominion\Services\Dominion\Actions;

use DB;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;
use Throwable;

class ConstructActionService
{
    use DominionGuardsTrait;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ConstructionCalculator */
    protected $constructionCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var QueueService */
    protected $queueService;

    /**
     * ConstructionActionService constructor.
     */
    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->constructionCalculator = app(ConstructionCalculator::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->queueService = app(QueueService::class);
    }

    /**
     * Does a construction action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Throwable
     */
    public function construct(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_only($data, array_map(function ($value) {
            return "building_{$value}";
        }, $this->buildingHelper->getBuildingTypes()));

        $data = array_map('\intval', $data);

        $totalBuildingsToConstruct = array_sum($data);

        if ($totalBuildingsToConstruct === 0) {
            throw new RuntimeException('Construction was not started due to bad input.');
        }

        $maxAfford = $this->constructionCalculator->getMaxAfford($dominion);

        if ($totalBuildingsToConstruct > $maxAfford) {
            throw new RuntimeException("You do not have enough platinum and/or lumber to construct {$totalBuildingsToConstruct} buildings.");
        }

        $buildingsByLandType = [];

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $landType = $this->landHelper->getLandTypeForBuildingByRace(
                str_replace('building_', '', $buildingType),
                $dominion->race
            );

            if (!isset($buildingsByLandType[$landType])) {
                $buildingsByLandType[$landType] = 0;
            }

            $buildingsByLandType[$landType] += $amount;
        }

        foreach ($buildingsByLandType as $landType => $amount) {
            if ($amount > $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType)) {
                throw new RuntimeException("You do not have enough barren land to construct {$totalBuildingsToConstruct} buildings.");
            }
        }

        $platinumCost = ($this->constructionCalculator->getPlatinumCost($dominion) * $totalBuildingsToConstruct);
        $lumberCost = ($this->constructionCalculator->getLumberCost($dominion) * $totalBuildingsToConstruct);

        // Check for discounted acres after invasion
        $discountedBuildings = min($dominion->discounted_land, $totalBuildingsToConstruct);
        $platinumDiscount = 0;
        $lumberDiscount = 0;

        if ($discountedBuildings > 0) {
            // Calculate half cost
            $platinumDiscount = (int)ceil(($this->constructionCalculator->getPlatinumCost($dominion) * $discountedBuildings) / 2); // todo: constant this with -50% or something
            $lumberDiscount = (int)ceil(($this->constructionCalculator->getLumberCost($dominion) * $discountedBuildings) / 2);

            $platinumCost -= $platinumDiscount;
            $lumberCost -= $lumberDiscount;
        }

        $newPlatinum = ($dominion->resource_platinum - $platinumCost);
        $newLumber = ($dominion->resource_lumber - $lumberCost);

        DB::transaction(function () use ($dominion, $data, $newPlatinum, $newLumber, $discountedBuildings) {
            $dominion->fill([
                'resource_platinum' => $newPlatinum,
                'resource_lumber' => $newLumber,
                'discounted_land' => ($dominion->discounted_land - $discountedBuildings),
            ])->save(['event' => HistoryService::EVENT_ACTION_CONSTRUCT]);

            $this->queueService->queueResources('construction', $dominion, $data);
        });

        $message = sprintf(
            'Construction started at a cost of %s platinum and %s lumber.',
            number_format($platinumCost),
            number_format($lumberCost)
        );

        if ($discountedBuildings > 0) {
            $message .= sprintf(
                ' Because of %s acres of conquered land, you saved %s platinum and %s lumber.',
                number_format($discountedBuildings),
                number_format($platinumDiscount),
                number_format($lumberDiscount)
            );
        }

        return [
            'message' => $message,
            'data' => [
                'platinumCost' => $platinumCost,
                'lumberCost' => $lumberCost,
            ],
        ];
    }
}
