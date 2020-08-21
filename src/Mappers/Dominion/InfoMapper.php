<?php

namespace OpenDominion\Mappers\Dominion;

use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class InfoMapper
{
    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var ImprovementCalculator */
    protected $improvementCalculator;

    /** @var ImprovementHelper */
    protected $improvementHelper;

    /** @var LandCalculator */
    protected $landCalculator;

    /** @var LandHelper */
    protected $landHelper;

    /** @var MilitaryCalculator */
    protected $militaryCalculator;

    /** @var NetworthCalculator */
    protected $networthCalculator;

    /** @var PopulationCalculator */
    protected $populationCalculator;

    /** @var QueueService */
    protected $queueService;

    public function __construct()
    {
        $this->buildingHelper = app(BuildingHelper::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->queueService = app(QueueService::class);
    }

    public function mapStatus(Dominion $dominion, bool $isOp = true): array
    {
        $data = [
            'ruler_name' => $dominion->ruler_name,
            'race_id' => $dominion->race->id,
            'land' => $this->landCalculator->getTotalLand($dominion),
            'peasants' => $dominion->peasants,
            'employment' => $this->populationCalculator->getEmploymentPercentage($dominion),
            'networth' => $this->networthCalculator->getDominionNetworth($dominion),
            'prestige' => $dominion->prestige,

            'resource_platinum' => $dominion->resource_platinum,
            'resource_food' => $dominion->resource_food,
            'resource_lumber' => $dominion->resource_lumber,
            'resource_mana' => $dominion->resource_mana,
            'resource_ore' => $dominion->resource_ore,
            'resource_gems' => $dominion->resource_gems,
            'resource_tech' => $dominion->resource_tech,
            'resource_boats' => $dominion->resource_boats + $this->queueService->getInvasionQueueTotalByResource(
                    $dominion,
                    'resource_boats'
                ),

            'morale' => $dominion->morale,
            'military_draftees' => $dominion->military_draftees,
            'military_unit1' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1),
            'military_unit2' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2),
            'military_unit3' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3),
            'military_unit4' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4),
            'military_spies' => null,
            'military_wizards' => null,
            'military_archmages' => null,

            'recently_invaded_count' => null,
        ];

        if(!$isOp) {
            $data['military_spies'] = $dominion->military_spies;
            $data['military_wizards'] = $dominion->military_wizards;
            $data['military_archmages'] = $dominion->military_archmages;
        } else {
            $data['recently_invaded_count'] = $this->militaryCalculator->getRecentlyInvadedCount($dominion);
        }

        return $data;
    }

    public function mapTechs(Dominion $dominion): array
    {
        return $dominion->techs->pluck('name', 'key')->all();
    }

    public function mapBarracks(Dominion $dominion, bool $isOp = true): array
    {
        $accuracyMultiplier = 1;

        if($isOp) {
            $accuracyMultiplier = 0.85;
        }

        $data = [
            'units' => [
                'home' => [],
                'returning' => [],
                'training' => [],
            ],
        ];

        array_set($data, 'units.home.draftees', random_int(
            round($dominion->military_draftees * $accuracyMultiplier),
            round($dominion->military_draftees / $accuracyMultiplier)
        ));

        foreach (range(1, 4) as $slot) {
            $amountAtHome = $dominion->{'military_unit' . $slot};

            if ($amountAtHome !== 0) {
                $amountAtHome = random_int(
                    round($amountAtHome * $accuracyMultiplier),
                    round($amountAtHome / $accuracyMultiplier)
                );
            }

            array_set($data, "units.home.unit{$slot}", $amountAtHome);
        }

        $this->queueService->getInvasionQueue($dominion)->each(static function ($row) use (&$data, $accuracyMultiplier) {
            if (!starts_with($row->resource, 'military_')) {
                return; // continue
            }

            $unitType = str_replace('military_', '', $row->resource);

            $amount = random_int(
                round($row->amount * $accuracyMultiplier),
                round($row->amount / $accuracyMultiplier)
            );

            array_set($data, "units.returning.{$unitType}.{$row->hours}", $amount);
        });

        $this->queueService->getTrainingQueue($dominion)->each(static function ($row) use (&$data) {
            $unitType = str_replace('military_', '', $row->resource);

            array_set($data, "units.training.{$unitType}.{$row->hours}", $row->amount);
        });

        return $data;
    }

    public function mapImprovements(Dominion $dominion): array
    {
        $data = [];

        foreach ($this->improvementHelper->getImprovementTypes() as $type) {
            array_set($data, "{$type}.points", $dominion->{'improvement_' . $type});
            array_set($data, "{$type}.rating",
                $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $type));
        }

        return $data;
    }

    public function mapBuildings(Dominion $dominion): array
    {
        $data = [];

        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            array_set($data, "constructed.{$buildingType}", $dominion->{'building_' . $buildingType});
        }

        $this->queueService->getConstructionQueue($dominion)->each(static function ($row) use (&$data) {
            $buildingType = str_replace('building_', '', $row->resource);

            array_set($data, "constructing.{$buildingType}.{$row->hours}", $row->amount);
        });

        array_set($data, 'barren_land', $this->landCalculator->getTotalBarrenLand($dominion));
        array_set($data, 'total_land', $this->landCalculator->getTotalLand($dominion));

        return $data;
    }

    public function mapLand(Dominion $dominion): array
    {
        $data = [];

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $amount = $dominion->{'land_' . $landType};

            array_set($data, "explored.{$landType}.amount", $amount);
            array_set($data, "explored.{$landType}.percentage",
                (($amount / $this->landCalculator->getTotalLand($dominion)) * 100));
            array_set($data, "explored.{$landType}.barren",
                $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType));
        }

        $this->queueService->getExplorationQueue($dominion)->each(static function ($row) use (&$data) {
            $landType = str_replace('land_', '', $row->resource);

            array_set(
                $data,
                "incoming.{$landType}.{$row->hours}",
                (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
            );
        });

        $this->queueService->getInvasionQueue($dominion)->each(static function ($row) use (&$data) {
            if (!starts_with($row->resource, 'land_')) {
                return; // continue
            }

            $landType = str_replace('land_', '', $row->resource);

            array_set(
                $data,
                "incoming.{$landType}.{$row->hours}",
                (array_get($data, "incoming.{$landType}.{$row->hours}", 0) + $row->amount)
            );
        });
    }
}