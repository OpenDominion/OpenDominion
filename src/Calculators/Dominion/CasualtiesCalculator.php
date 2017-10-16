<?php

namespace OpenDominion\Calculators\Dominion;

use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class CasualtiesCalculator
{
    /** @var UnitHelper */
    private $unitHelper;

    public function __construct(UnitHelper $unitHelper)
    {
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the Dominion's casualties.
     *
     * @param  Dominion $dominion
     * @return int
     */
    public function getTotalCasualties(Dominion $dominion): int
    {
        if ($dominion->resource_food >= 0) {
            return 0;
        }

        return (int)(abs($dominion->resource_food) * 4);
    }

    /**
     * Returns the Dominion's casualties by unit type.
     *
     * @param  Dominion $dominion
     * @return array
     */
    public function getCasualtiesByUnitType(Dominion $dominion): array
    {
        $units = $this->getUnitTypes();

        $totalCasualties = $this->getTotalCasualties($dominion);

        $casualties = [
            'peasants' => min($totalCasualties / 2, $dominion->peasants),
        ];

        $remainingCasualties = $totalCasualties - array_sum($casualties);

        while (count($units) > 0 && $remainingCasualties > 0) {
            foreach ($units as $unit) {
                $casualties[$unit] = min(
                    array_get($casualties, $unit, 0) + (int)(ceil($remainingCasualties / count($units))),
                    $dominion->{$unit}
                );
            }

            $remainingCasualties = $totalCasualties - array_sum($casualties);

            $units = array_filter($units, function ($unit) use ($dominion, $casualties) {
                return $casualties[$unit] < $dominion->{$unit};
            });
        }

        return array_filter($casualties);
    }

    /**
     * Returns the Units that can suffer casualties.
     *
     * @return array
     */
    protected function getUnitTypes(): array
    {
        return array_map(function ($unit) {
            return 'military_' . $unit;
        }, array_merge($this->unitHelper->getUnitTypes(), ['draftees']));
    }
}
