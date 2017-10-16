<?php

namespace OpenDominion\Calculators\Dominion\Actions;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Helpers\UnitHelper;
use OpenDominion\Models\Dominion;

class TrainingCalculator
{
    /** @var LandCalculator */
    protected $landCalculator;

    /** @var UnitHelper */
    protected $unitHelper;

    /**
     * TrainingCalculator constructor.
     *
     * @param UnitHelper $unitHelper
     */
    public function __construct(LandCalculator $landCalculator, UnitHelper $unitHelper)
    {
        $this->landCalculator = $landCalculator;
        $this->unitHelper = $unitHelper;
    }

    /**
     * Returns the Dominion's training costs per unit.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getTrainingCostsPerUnit(Dominion $dominion): array
    {
        $costsPerUnit = [];

        // Values
        $spyPlatinumCost = 500;
        $wizardPlatinumCost = 500;
        $archmagePlatinumCost = 1000;

        $units = $dominion->race->units;

        foreach ($this->unitHelper->getUnitTypes() as $unitType) {
            $cost = [];

            switch ($unitType) {
                case 'spies':
                    $cost['draftees'] = 1;
                    $cost['platinum'] = $spyPlatinumCost;
                    break;

                case 'wizards':
                    $cost['draftees'] = 1;
                    $cost['platinum'] = $wizardPlatinumCost;
                    break;

                case 'archmages':
                    $cost['platinum'] = $archmagePlatinumCost;
                    $cost['wizards'] = 1;
                    break;

                default:
                    $unitSlot = (((int)str_replace('unit', '', $unitType)) - 1);

                    $platinum = $units[$unitSlot]->cost_platinum;
                    $ore = $units[$unitSlot]->cost_ore;

                    if ($platinum > 0) {
                        $cost['platinum'] = (int)ceil($platinum * $this->getSpecialistEliteCostMultiplier($dominion));
                    }

                    if ($ore > 0) {
                        $cost['ore'] = (int)ceil($ore * $this->getSpecialistEliteCostMultiplier($dominion));
                    }

                    $cost['draftees'] = 1;

                    break;
            }

            $costsPerUnit[$unitType] = $cost;
        }

        return $costsPerUnit;
    }

    /**
     * Returns the Dominion's max military trainable population.
     *
     * @param Dominion $dominion
     * @return array
     */
    public function getMaxTrainable(Dominion $dominion): array
    {
        $trainable = [];

        $fieldMapping = [
            'platinum' => 'resource_platinum',
            'ore' => 'resource_ore',
            'draftees' => 'military_draftees',
            'wizards' => 'military_wizards',
        ];

        $costsPerUnit = $this->getTrainingCostsPerUnit($dominion);

        foreach ($costsPerUnit as $unitType => $costs) {
            $trainableByCost = [];

            foreach ($costs as $type => $value) {
                $trainableByCost[$type] = (int)floor($dominion->{$fieldMapping[$type]} / $value);
            }

            $trainable[$unitType] = min($trainableByCost);
        }

        return $trainable;
    }

    /**
     * Returns the Dominion's training cost multiplier.
     *
     * @param Dominion $dominion
     * @return float
     */
    public function getSpecialistEliteCostMultiplier(Dominion $dominion): float
    {
        $multiplier = 1.0;

        // Values (percentages)
        $smithiesReduction = 2;
        $smithiesReductionMax = 36;

        // Smithies
        $multiplier -= min(
            (($dominion->building_smithy / $this->landCalculator->getTotalLand($dominion)) * $smithiesReduction),
            ($smithiesReductionMax / 100)
        );

        // todo: Master of Resources Tech (note: no ore reduction for gnomes)

        return $multiplier;
    }
}
