<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Helpers\BuildingHelper;
use OpenDominion\Helpers\LandHelper;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Services\Dominion\InfoOpAssemblerService;

class AdvisorsController extends AbstractController
{
    /**
     * Spending categories tracked per resource by stat_total_{resource}_spent_{category}.
     */
    private const SPENDING_CATEGORIES = [
        'platinum' => ['construction', 'exploration', 'investment', 'rezoning', 'training'],
        'lumber' => ['construction', 'investment', 'training'],
        'mana' => ['investment', 'training'],
        'ore' => ['investment', 'training'],
        'gems' => ['investment', 'training'],
    ];

    public function __construct(
        private InfoOpAssemblerService $assembler,
        private BuildingHelper $buildingHelper,
        private LandHelper $landHelper,
        private MilitaryCalculator $militaryCalculator,
        private PopulationCalculator $populationCalculator,
        private ProductionCalculator $productionCalculator
    ) {
    }

    /**
     * Current data for the key's own dominion and every realmie who shares
     * their advisors with it, as on the in-game realm advisors pages.
     * Not gated by protection or round start, like the advisors themselves.
     */
    public function index(): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $realm = $dominion->realm;

        $visible = $realm->dominions()
            ->with(['race', 'realm', 'round', 'user'])
            ->orderBy('id')
            ->get()
            ->filter(fn (Dominion $realmie) => $dominion->inRealmAndSharesAdvisors($realmie))
            ->sortBy(fn (Dominion $realmie) => $realmie->id === $dominion->id ? 0 : 1);

        $dominions = [];
        foreach ($visible as $realmie) {
            $dominions[(string) $realmie->id] = $this->dominionPayload($realmie);
        }

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'realm' => [
                'id' => $realm->id,
                'number' => $realm->number,
                'name' => $realm->name,
            ],
            'dominions' => (object) $dominions,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function dominionPayload(Dominion $dominion): array
    {
        return [
            'id' => $dominion->id,
            'name' => $dominion->name,
            'race' => $dominion->race?->name,
            'ops' => $this->assembler->assembleFromAdvisors($dominion),
            'resources' => $this->resourcesPayload($dominion),
            'military' => $this->militaryPayload($dominion),
            'land' => $this->landPayload($dominion),
            'buildings' => $this->buildingsPayload($dominion),
            'hourly' => $this->hourlyPayload($dominion),
            'population' => $this->populationPayload($dominion),
            'statistics' => $this->statisticsPayload($dominion),
        ];
    }

    /**
     * Current stockpiles.
     *
     * @return array<string, int|float>
     */
    private function resourcesPayload(Dominion $dominion): array
    {
        return [
            'platinum' => $dominion->resource_platinum,
            'food' => $dominion->resource_food,
            'lumber' => $dominion->resource_lumber,
            'mana' => $dominion->resource_mana,
            'ore' => $dominion->resource_ore,
            'gems' => $dominion->resource_gems,
            'tech' => $dominion->resource_tech,
            'boats' => round($dominion->resource_boats, 2),
        ];
    }

    /**
     * Units, spy/wizard strength and OP/DP modifiers.
     * Units 1-4 include those returning from invasion, as on the status page;
     * units in training are not included.
     * Modifiers are percentages, as on the Military advisor (+23.5 means x1.235).
     *
     * @return array<string, int|float|array<string, float>>
     */
    private function militaryPayload(Dominion $dominion): array
    {
        return [
            'draftees' => $dominion->military_draftees,
            'unit1' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 1),
            'unit2' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 2),
            'unit3' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 3),
            'unit4' => $this->militaryCalculator->getTotalUnitsForSlot($dominion, 4),
            'spies' => $dominion->military_spies,
            'assassins' => $dominion->military_assassins,
            'wizards' => $dominion->military_wizards,
            'archmages' => $dominion->military_archmages,
            'spy_strength' => round($dominion->spy_strength, 2),
            'wizard_strength' => round($dominion->wizard_strength, 2),
            'offensive_modifier' => round(($this->militaryCalculator->getOffensivePowerMultiplier($dominion) - 1) * 100, 3),
            'defensive_modifier' => round(($this->militaryCalculator->getDefensivePowerMultiplier($dominion) - 1) * 100, 3),
            'spy_ratio' => [
                'offense' => round($this->militaryCalculator->getSpyRatio($dominion, 'offense'), 3),
                'defense' => round($this->militaryCalculator->getSpyRatio($dominion, 'defense'), 3),
            ],
            'wizard_ratio' => [
                'offense' => round($this->militaryCalculator->getWizardRatio($dominion, 'offense'), 3),
                'defense' => round($this->militaryCalculator->getWizardRatio($dominion, 'defense'), 3),
            ],
        ];
    }

    /**
     * Acres of each land type (the dominion's land_* attributes).
     *
     * @return array<string, int>
     */
    private function landPayload(Dominion $dominion): array
    {
        $land = [];
        foreach ($this->landHelper->getLandTypes() as $landType) {
            $land[$landType] = (int) $dominion->{"land_{$landType}"};
        }

        return $land;
    }

    /**
     * Constructed buildings of each type (the dominion's building_* attributes).
     *
     * @return array<string, int>
     */
    private function buildingsPayload(Dominion $dominion): array
    {
        $buildings = [];
        foreach ($this->buildingHelper->getBuildingTypes() as $buildingType) {
            $buildings[$buildingType] = (int) $dominion->{"building_{$buildingType}"};
        }

        return $buildings;
    }

    /**
     * Hourly figures from the Production advisor.
     *
     * @return array{production: array<string, int|float>, consumption: array<string, int>, decay: array<string, int>, net_change: array<string, int>}
     */
    private function hourlyPayload(Dominion $dominion): array
    {
        return [
            'production' => [
                'platinum' => $this->productionCalculator->getPlatinumProduction($dominion),
                'food' => $this->productionCalculator->getFoodProduction($dominion),
                'lumber' => $this->productionCalculator->getLumberProduction($dominion),
                'mana' => $this->productionCalculator->getManaProduction($dominion),
                'ore' => $this->productionCalculator->getOreProduction($dominion),
                'gems' => $this->productionCalculator->getGemProduction($dominion),
                'tech' => $this->productionCalculator->getTechProduction($dominion),
                'boats' => round($this->productionCalculator->getBoatProduction($dominion), 2),
            ],
            'consumption' => [
                'food' => (int) round($this->productionCalculator->getFoodConsumption($dominion)),
            ],
            'decay' => [
                'food' => (int) round($this->productionCalculator->getFoodDecay($dominion)),
                'lumber' => (int) round($this->productionCalculator->getLumberDecay($dominion)),
                'mana' => (int) round($this->productionCalculator->getManaDecay($dominion)),
            ],
            'net_change' => [
                'food' => $this->productionCalculator->getFoodNetChange($dominion),
                'lumber' => $this->productionCalculator->getLumberNetChange($dominion),
                'mana' => $this->productionCalculator->getManaNetChange($dominion),
            ],
        ];
    }

    /**
     * Population figures from the Production advisor.
     *
     * @return array<string, int>
     */
    private function populationPayload(Dominion $dominion): array
    {
        return [
            'total' => $this->populationCalculator->getPopulation($dominion),
            'max' => $this->populationCalculator->getMaxPopulation($dominion),
            'peasants' => $dominion->peasants,
            'military' => $this->populationCalculator->getPopulationMilitary($dominion),
            'jobs' => $this->populationCalculator->getEmploymentJobs($dominion),
            'employed' => $this->populationCalculator->getPopulationEmployed($dominion),
        ];
    }

    /**
     * Round totals from the dominion's stat_total_* counters. For each resource,
     * {resource}_spent is the total and {resource}_spent_{category} the
     * breakdown, one per stat_total_{resource}_spent_{category} column.
     *
     * @return array<string, int>
     */
    private function statisticsPayload(Dominion $dominion): array
    {
        $statistics = [];

        foreach (self::SPENDING_CATEGORIES as $resource => $categories) {
            $breakdown = [];
            foreach ($categories as $category) {
                $breakdown["{$resource}_spent_{$category}"] = (int) $dominion->{"stat_total_{$resource}_spent_{$category}"};
            }

            $statistics["{$resource}_spent"] = array_sum($breakdown);
            $statistics += $breakdown;
        }

        return $statistics;
    }

    private function getApiDominion(): Dominion
    {
        return app('api.dominion');
    }
}
