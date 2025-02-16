<?php

namespace OpenDominion\Mappers\Dominion;

use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\HeroCalculator;
use OpenDominion\Calculators\Dominion\ImprovementCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\SpellCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\ImprovementHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\QueueService;

class InfoMapper
{
    /* @var BuildingCalculator */
    private $buildingCalculator;

    /** @var BuildingHelper */
    protected $buildingHelper;

    /** @var HeroCalculator */
    protected $heroCalculator;

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

    /** @var SpellCalculator */
    protected $spellCalculator;

    public function __construct()
    {
        $this->buildingCalculator = app(BuildingCalculator::class);
        $this->buildingHelper = app(BuildingHelper::class);
        $this->heroCalculator = app(HeroCalculator::class);
        $this->improvementCalculator = app(ImprovementCalculator::class);
        $this->improvementHelper = app(ImprovementHelper::class);
        $this->landCalculator = app(LandCalculator::class);
        $this->landHelper = app(LandHelper::class);
        $this->militaryCalculator = app(MilitaryCalculator::class);
        $this->networthCalculator = app(NetworthCalculator::class);
        $this->populationCalculator = app(PopulationCalculator::class);
        $this->queueService = app(QueueService::class);
        $this->spellCalculator = app(SpellCalculator::class);
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
            'resilience' => $dominion->resilience,
            'spy_mastery' => $dominion->spy_mastery,
            'wizard_mastery' => $dominion->wizard_mastery,

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
            'military_draftees' => null,
            'military_unit1' => null,
            'military_unit2' => null,
            'military_unit3' => null,
            'military_unit4' => null,
            'military_spies' => null,
            'military_assassins' => null,
            'military_wizards' => null,
            'military_archmages' => null,

            'recently_invaded_count' => null,
            'clear_sight_accuracy' => null,
        ];

        $militaryAccuracy = 1;

        if(!$isOp) {
            $data['military_spies'] = $dominion->military_spies;
            $data['military_assassins'] = $dominion->military_assassins;
            $data['military_wizards'] = $dominion->military_wizards;
            $data['military_archmages'] = $dominion->military_archmages;
        } else {
            // Wonders
            // - Spire of Illusion: Clear Sights are 85% accurate
            if ($dominion->getWonderPerkMultiplier('clear_sight_accuracy') != 0) {
                $militaryAccuracy = $dominion->getWonderPerkMultiplier('clear_sight_accuracy');
                $data['clear_sight_accuracy'] = $militaryAccuracy;
            }

            $data['recently_invaded_count'] = $this->militaryCalculator->getRecentlyInvadedCount($dominion);
        }

        //$data['spa'] = $this->militaryCalculator->getSpyRatioRaw($dominion);
        $data['wpa'] = $this->militaryCalculator->getWizardRatioRaw($dominion, 'defense');

        $military_draftees = $dominion->military_draftees;
        $military_unit1 = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1);
        $military_unit2 = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2);
        $military_unit3 = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3);
        $military_unit4 = $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4);

        $data['military_draftees'] = random_int(
            round($military_draftees * $militaryAccuracy),
            round($military_draftees / $militaryAccuracy)
        );

        $data['military_unit1'] = random_int(
            round($military_unit1 * $militaryAccuracy),
            round($military_unit1 / $militaryAccuracy)
        );

        $data['military_unit2'] = random_int(
            round($military_unit2 * $militaryAccuracy),
            round($military_unit2 / $militaryAccuracy)
        );

        $data['military_unit3'] = random_int(
            round($military_unit3 * $militaryAccuracy),
            round($military_unit3 / $militaryAccuracy)
        );

        $data['military_unit4'] = random_int(
            round($military_unit4 * $militaryAccuracy),
            round($military_unit4 / $militaryAccuracy)
        );

        return $data;
    }

    public function mapSpells(Dominion $dominion): array
    {
        $data = [];

        foreach ($this->spellCalculator->getActiveSpells($dominion) as $activeSpell) {
            $spellData = $activeSpell->toArray();
            unset($spellData['cast_by_dominion']);
            $spellData['spell'] = $activeSpell->spell->key;
            $spellData['cast_by_dominion_name'] = $activeSpell->castByDominion->name;
            $spellData['cast_by_dominion_realm_number'] = $activeSpell->castByDominion->realm->number;
            $data[] = $spellData;
        }

        return $data;
    }

    public function mapImprovements(Dominion $dominion): array
    {
        $data = [];

        foreach ($this->improvementHelper->getImprovementTypes() as $type) {
            array_set($data, "{$type}.points", $dominion->{'improvement_' . $type});
            array_set(
                $data,
                "{$type}.rating",
                $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $type)
            );
            if ($type == 'spires' || $type == 'harbor') {
                array_set(
                    $data,
                    "{$type}.rating_secondary",
                    $this->improvementCalculator->getImprovementMultiplierBonus($dominion, $type, true)
                );
            }
            array_set(
                $data,
                "{$type}.incoming",
                $this->queueService->getQueueTotalByResource('operations', $dominion, "improvement_{$type}")
            );
        }

        array_set($data, 'total', $this->improvementCalculator->getImprovementTotal($dominion));

        return $data;
    }

    public function mapMilitary(Dominion $dominion, bool $isOp = true): array
    {
        $accuracyMultiplier = 1;

        $data = [
            'units' => [
                'home' => [],
                'returning' => [],
                'training' => [],
            ],
        ];

        if($isOp) {
            $accuracyMultiplier = 0.85;
        } else {
            array_set($data, 'units.home.spies', $dominion->military_spies);
            array_set($data, 'units.home.assassins', $dominion->military_assassins);
            array_set($data, 'units.home.wizards', $dominion->military_wizards);
            array_set($data, 'units.home.archmages', $dominion->military_archmages);
        }

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
        $totalLand = $this->landCalculator->getTotalLand($dominion);
        $totalBarrenLand = $this->landCalculator->getTotalBarrenLand($dominion);
        $totalConstructedLand = $this->buildingCalculator->getTotalBuildings($dominion);

        $data = [
            'totalLand' => $totalLand,
            'totalBarrenLand' => $totalBarrenLand,
            'totalConstructedLand' => $totalConstructedLand,
        ];

        if($totalConstructedLand === 0) {
            $totalConstructedLand = 1;
        }

        foreach ($this->landHelper->getLandTypes() as $landType) {
            $amount = $dominion->{'land_' . $landType};

            array_set($data, "explored.{$landType}.amount", $amount);
            array_set(
                $data,
                "explored.{$landType}.percentage",
                (($amount / $totalLand) * 100)
            );

            array_set(
                $data,
                "explored.{$landType}.barren",
                $this->landCalculator->getTotalBarrenLandByLandType($dominion, $landType)
            );

            $totalConstructedForLandType = $this->buildingCalculator->getTotalBuildingsForLandType($dominion, $landType);

            array_set(
                $data,
                "explored.{$landType}.constructed",
                $totalConstructedForLandType
            );

            array_set(
                $data,
                "explored.{$landType}.constructedPercentage",
                (($totalConstructedForLandType / $totalConstructedLand) * 100)
            );
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

        return $data;
    }

    public function mapTechs(Dominion $dominion): array
    {
        return $dominion->techs->pluck('name', 'key')->all();
    }

    public function mapHeroes(Dominion $dominion): array
    {
        if (!$dominion->hero) {
            return [];
        }

        return [
            [
                'name' => $dominion->hero->name,
                'class' => $dominion->hero->class,
                'level' => $this->heroCalculator->getHeroLevel($dominion->hero),
                'experience' => rfloor($dominion->hero->experience),
                'next_level_xp' => $this->heroCalculator->getNextLevelXP($dominion->hero),
                'bonus' => $this->heroCalculator->getPassiveBonus($dominion->hero),
                'upgrades' => $dominion->hero->upgrades->pluck('name', 'key')->all()
            ]
        ];
    }

    public function mapResources(Dominion $dominion): array
    {
        $data = ['incoming' => []];

        $this->queueService->getInvasionQueue($dominion)->each(static function ($row) use (&$data) {
            if (!starts_with($row->resource, 'resource_') && $row->resource !== 'prestige') {
                return; // continue
            }

            $resourceType = str_replace('resource_', '', $row->resource);

            array_set(
                $data,
                "incoming.{$resourceType}.{$row->hours}",
                (array_get($data, "incoming.{$resourceType}.{$row->hours}", 0) + $row->amount)
            );
        });

        return $data;
    }
}
