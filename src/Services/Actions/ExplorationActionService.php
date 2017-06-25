<?php

namespace OpenDominion\Services\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Contracts\Calculators\Dominion\Actions\ExplorationCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class ExplorationActionService
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
     * @throws DominionLockedException
     * @throws Exception
     * @throws BadInputException
     * @throws NotEnoughResourcesException
     */
    public function explore(Dominion $dominion, array $data)
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('intval', $data);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore === 0) {
            throw new BadInputException;
        }

        $maxAfford = $this->explorationCalculator->getMaxAfford($dominion);

        if ($totalLandToExplore > $maxAfford) {
            throw new NotEnoughResourcesException;
        }

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
                ]);

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

        return compact('platinumCost', 'drafteeCost', 'moraleDrop');
    }
}
