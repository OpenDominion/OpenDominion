<?php

namespace OpenDominion\Services\Actions;

use Carbon\Carbon;
use DB;
use Exception;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Exceptions\DominionLockedException;
use OpenDominion\Exceptions\NotEnoughResourcesException;
use OpenDominion\Models\Dominion;
use OpenDominion\Traits\DominionGuardsTrait;

class ExplorationActionService
{
    use DominionGuardsTrait;

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

        /** @var LandCalculator $landCalculator */
        $landCalculator = app(LandCalculator::class);
        $landCalculator->init($dominion);

        $totalLandToExplore = array_sum($data);

        if ($totalLandToExplore === 0) {
            throw new BadInputException;
        }

        $maxAfford = $landCalculator->getExplorationMaxAfford();

        if ($totalLandToExplore > $maxAfford) {
            throw new NotEnoughResourcesException;
        }

        $newMorale = max(0, $dominion->morale - ($totalLandToExplore * $landCalculator->getExplorationMoraleDrop($totalLandToExplore)));
        $moraleDrop = ($dominion->morale - $newMorale);

        $platinumCost = ($landCalculator->getExplorationPlatinumCost() * $totalLandToExplore);
        $newPlatinum = ($dominion->resource_platinum - $platinumCost);

        $drafteeCost = ($landCalculator->getExplorationDrafteeCost() * $totalLandToExplore);
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
