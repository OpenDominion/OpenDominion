<?php

namespace OpenDominion\Services\Dominion\Actions;

use OpenDominion\Calculators\Dominion\Actions\ConstructionCalculator;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Traits\DominionGuardsTrait;

class DestroyActionService
{
    use DominionGuardsTrait;

    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws GameException
     */
    public function destroy(Dominion $dominion, array $data): array
    {
        $this->guardLockedDominion($dominion);

        $data = array_map('\intval', $data);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy === 0) {
            throw new GameException('The destruction was not completed due to bad input.');
        }

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount < 0) {
                throw new GameException('Destruction was not completed due to bad input.');
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new GameException('The destruction was not completed due to bad input.');
            }
        }

        foreach ($data as $buildingType => $amount) {
            $dominion->{'building_' . $buildingType} -= $amount;
        }

        $destructionRefundString = '';
        if ($dominion->getTechPerkValue('destruction_refund') !== 0) {
            $constructionCalculator = app(ConstructionCalculator::class);
            $multiplier =  $totalBuildingsToDestroy * $dominion->getTechPerkMultiplier('destruction_refund');
            $platinumRefund = round($constructionCalculator->getPlatinumCost($dominion) * $multiplier);
            $lumberRefund = round($constructionCalculator->getLumberCost($dominion) * $multiplier);
            $destructionRefundString = " You were refunded {$platinumRefund} platnium and {$lumberRefund} lumber.";
            $dominion->resource_platinum += $platinumRefund;
            $dominion->resource_lumber += $lumberRefund;
        }

        $dominion->save(['event' => HistoryService::EVENT_ACTION_DESTROY]);

        return [
            'message' => sprintf(
                'Destruction of %s %s is complete.%s',
                number_format($totalBuildingsToDestroy),
                str_plural('building', $totalBuildingsToDestroy),
                $destructionRefundString
            ),
            'data' => [
                'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
            ],
        ];
    }
}
