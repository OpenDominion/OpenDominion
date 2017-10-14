<?php

namespace OpenDominion\Services\Dominion\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;
use RuntimeException;

class ExploreActionService
{
    use DominionGuardsTrait;

    /** @var ExplorationCalculator */
    protected $explorationCalculator;

    /**
     * ExplorationActionService constructor.
     *
     * @param ExplorationCalculator $explorationCalculator
     */
    public function __construct(ExplorationCalculator $explorationCalculator)
    {
        $this->explorationCalculator = $explorationCalculator;
    }

    /**
     * Does an explore action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws Exception
     * @throws RuntimeException
     */
    public function explore(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore === 0) {
            throw new RuntimeException('Exploration was not begun due to bad input.');
        }

        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);

        if ($totalLandToExplore > $maxAfford) {
            throw new RuntimeException("You do not have enough platinum and/or draftees to explore for {$totalLandToExplore} acres.");
        }

        // todo: refactor. see training action service. same with other action services
        $newMorale = max(0, $dominion->morale - ($totalLandToExplore * $this->explorationCalculator->getMoraleDrop($totalLandToExplore)));
        $moraleDrop = ($dominion->morale - $newMorale);

        $platinumCost = ($this->explorationCalculator->getPlatinumCost($dominion) * $totalLandToExplore);
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $drafteeCost = ($this->explorationCalculator->getDrafteeCost($dominion) * $totalLandToExplore);
        $newDraftee = ($dominion->military_draftees - $drafteeCost);

        $dateTime = new Carbon;

        DB::beginTransaction();

        try {
            DB::table('dominions')
                ->where('id', $dominion->id)
                ->update([
                    'morale' => $newMorale,
                    'resource_platinum' => $newPlatinum,
                    'military_draftees' => $newDraftee,
                ]); // todo: use Eloquent

            // Check for existing queue
            $existingQueueRows = DB::table('queue_exploration')
                ->where([
                    'dominion_id' => $dominion->id,
                    'hours' => 12,
                ])->get(['land_type', 'amount']);

            foreach ($existingQueueRows as $row) {
                $data[$row->land_type] += $row->amount;
            }

            foreach ($data as $landType => $amount) {
                if ($amount === 0) {
                    continue;
                }

                $where = [
                    'dominion_id' => $dominion->id,
                    'land_type' => $landType,
                    'hours' => 12,
                ];

                $values = [
                    'amount' => $amount,
                    'updated_at' => $dateTime,
                ];

                if ($existingQueueRows->isEmpty()) {
                    $values['created_at'] = $dateTime;
                }

                DB::table('queue_exploration')
                    ->updateOrInsert($where, $values);
            }

            DB::commit();

        } catch (Exception $e) {
            DB::rollBack();

            throw $e;
        }

        return [
            'message' => sprintf('Exploration begun at a cost of %d platinum and %d draftees. Your orders for exploration disheartens the military, and morale drops %d%%.', $platinumCost, $drafteeCost, $moraleDrop),
            'data' => [
                'platinumCost' => $platinumCost,
                'drafteeCost' => $drafteeCost,
                'moraleDrop' => $moraleDrop,
            ]
        ];
    }
}
