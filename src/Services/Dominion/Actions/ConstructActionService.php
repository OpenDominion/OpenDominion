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

            $landType = $this->landHelper->getLandTypeForBuildingByRace(str_replace('building_', '', $buildingType),
                $dominion->race);

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
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $lumberCost = ($this->constructionCalculator->getLumberCost($dominion) * $totalBuildingsToConstruct);
        $newLumber = ($dominion->resource_lumber - $lumberCost);

        DB::transaction(function () use ($dominion, $data, $newPlatinum, $newLumber) {
            $dominion->fill([
                'resource_platinum' => $newPlatinum,
                'resource_lumber' => $newLumber,
            ])->save(['event' => HistoryService::EVENT_ACTION_CONSTRUCT]);

            $this->queueService->queueResources('construction', $dominion, $data);
        });

        return [
            'message' => sprintf(
                'Construction started at a cost of %s platinum and %s lumber.',
                number_format($platinumCost),
                number_format($lumberCost)
            ),
            'data' => [
                'platinumCost' => $platinumCost,
                'lumberCost' => $lumberCost,
            ],
        ];
    }
}
