<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class DailyBonusesActionService
{
    use DominionGuardsTrait;

    /**
     * Claims the daily platinum bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function claimPlatinum(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->daily_platinum) {
            throw new GameException('You already claimed your platinum bonus for today.');
        }

        $platinumGained = $dominion->peasants * 4;
        $researchPointsGained = 750;

        $dominion->resource_platinum += $platinumGained;
        $dominion->resource_tech += $researchPointsGained;
        $dominion->daily_platinum = true;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS]);

        return [
            'message' => sprintf(
                'You gain %s platinum and %s research points.',
                number_format($platinumGained),
                $researchPointsGained
            ),
            'data' => [
                'platinumGained' => $platinumGained,
            ],
        ];
    }

    /**
     * Claims the daily land bonus for a Dominion.
     *
     * @param Dominion $dominion
     * @return array
     * @throws GameException
     */
    public function claimLand(Dominion $dominion): array
    {
        $this->guardLockedDominion($dominion);

        if ($dominion->daily_land) {
            throw new GameException('You already claimed your land bonus for today.');
        }

        $landGained = 20;

        $landCalculator = app(LandCalculator::class);
        $landTotal = $landCalculator->getTotalLand($dominion);
        $dominion->highest_land_achieved = max($dominion->highest_land_achieved, $landTotal + $landGained);

        $attribute = ('land_' . $dominion->race->home_land_type);
        $dominion->{$attribute} += $landGained;

        $dominion->stat_total_land_explored += $landGained;
        $dominion->daily_land = true;
        $dominion->save(['event' => HistoryService::EVENT_ACTION_DAILY_BONUS]);

        return [
            'message' => sprintf(
                'You gain %d acres of %s.',
                $landGained,
                str_plural($dominion->race->home_land_type)
            ),
            'data' => [
                'landGained' => $landGained,
            ],
        ];
    }
}
