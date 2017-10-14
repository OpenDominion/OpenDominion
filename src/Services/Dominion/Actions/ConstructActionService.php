<?php

namespace OpenDominion\Services\Dominion\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ConstructActionService
{
    use DominionGuardsTrait;

    /** @var ConstructionCalculator */
    protected $constructionCalculator;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /**
     * ConstructionActionService constructor.
     *
     * @param ConstructionCalculator $constructionCalculator
     * @param LandCalculator $landCalculator
     * @param LandHelper $landHelper
     */
    public function __construct(ConstructionCalculator $constructionCalculator, LandCalculator $landCalculator, LandHelper $landHelper)
    {
        $this->constructionCalculator = $constructionCalculator;
        $this->landCalculator = $landCalculator;
        $this->landHelper = $landHelper;
    }

    /**
     * Does a construction action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Exception
     * @throws RuntimeException
     */
    public function construct(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        $totalBuildingsToConstruct = array_sum($data);

        if ($totalBuildingsToConstruct === 0) {
            throw new RuntimeException('Construction was not started due to bad input.');
        }

        $maxAfford = $this->constructionCalculator->getMaxAfford($dominion);

        if ($totalBuildingsToConstruct > $maxAfford) {
            throw new RuntimeException("You do not have enough platinum and/or lumber to construct {$totalBuildingsToConstruct} buildings.");
        }

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            $landType = $this->landHelper->getLandTypeForBuildingByRace($buildingType, $dominion->race);

            if ($amount > $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType)) {
                throw new RuntimeException("You do not have enough barren land to construct {$totalBuildingsToConstruct} buildings.");
            }
        }

        $platinumCost = ($this->constructionCalculator->getPlatinumCost($dominion) * $totalBuildingsToConstruct);
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $lumberCost = ($this->constructionCalculator->getLumberCost($dominion) * $totalBuildingsToConstruct);
        $newLumber = ($dominion->resource_lumber - $lumberCost);

        $dateTime = new Carbon;

        DB::beginTransaction();

        try {
            DB::table('dominions')
                ->where('id', $dominion->id)
                ->update([
                    'resource_platinum' => $newPlatinum,
                    'resource_lumber' => $newLumber,
                ]);

            // Check for existing queue
            $existingQueueRows = DB::table('queue_construction')
                ->where([
                    'dominion_id' => $dominion->id,
                    'hours' => 12,
                ])->get(['building', 'amount']);

            foreach ($existingQueueRows as $row) {
                $data[$row->building] += $row->amount;
            }

            foreach ($data as $buildingType => $amount) {
                if ($amount === 0) {
                    continue;
                }

                $where = [
                    'dominion_id' => $dominion->id,
                    'building' => $buildingType,
                    'hours' => 12,
                ];

                $values = [
                    'amount' => $amount,
                    'updated_at' => $dateTime,
                ];

                if ($existingQueueRows->isEmpty()) {
                    $values['created_at'] = $dateTime;
                }

                DB::table('queue_construction')
                    ->updateOrInsert($where, $values);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return [
            'message' => sprintf('Construction started at a cost of %d platinum and %d lumber.', $platinumCost, $lumberCost),
            'data' => [
                'platinumCost' => $platinumCost,
                'lumberCost' => $lumberCost,
            ],
        ];
    }
}
