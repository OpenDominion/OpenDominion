<?php

namespace OpenDominion\Services\Actions;

use DB;
use Exception;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Exceptions\BadInputException;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;

class DestroyActionService
{
    /**
     * Does a destroy buildings action for a Dominion.
     *
     * @param Dominion $dominion
     * @param array $data
     * @return array
     * @throws BadInputException
     */
    public function destroy(Dominion $dominion, array $data)
    {
        $data = array_map('intval', $data);

        /** @var BuildingCalculator $buildingCalculator */
        $buildingCalculator = app(BuildingCalculator::class)
            ->init($dominion);

        /** @var LandCalculator $landCalculator */
        $landCalculator = app(LandCalculator::class)
            ->init($dominion);

        /** @var LandHelper $landHelper */
        $landHelper = app(LandHelper::class);

        $totalBuildingsToDestroy = array_sum($data);

        if ($totalBuildingsToDestroy === 0) {
            throw new BadInputException;
        }

        foreach ($data as $buildingType => $amount) {
            if ($amount === 0) {
                continue;
            }

            if ($amount > $dominion->{'building_' . $buildingType}) {
                throw new BadInputException;
            }
        }

        foreach ($data as $buildingType => $amount) {
            $dominion->{'building_' . $buildingType} -= $amount;
        }

        $dominion->save();

        return [
            'totalBuildingsDestroyed' => $totalBuildingsToDestroy,
        ];
    }
}
