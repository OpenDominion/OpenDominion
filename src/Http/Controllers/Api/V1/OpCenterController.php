<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\MilitaryCalculator;
use OpenDominion\Calculators\Dominion\PopulationCalculator;
use OpenDominion\Calculators\Dominion\ProductionCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\InfoOpAssemblerService;

class OpCenterController extends AbstractController
{
    private const DEFAULT_MAX_AGE_HOURS = 12;
    private const DEFAULT_HISTORY_LIMIT = 100;
    private const MAX_HISTORY_LIMIT = 500;

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
        private MilitaryCalculator $militaryCalculator,
        private PopulationCalculator $populationCalculator,
        private ProductionCalculator $productionCalculator
    ) {
    }

    public function me(): JsonResponse
    {
        $dominion = $this->getApiDominion();

        return response()->json([
            'id' => $dominion->id,
            'name' => $dominion->name,
            'realm' => [
                'id' => $dominion->realm->id,
                'number' => $dominion->realm->number,
                'name' => $dominion->realm->name,
            ],
            'round' => [
                'id' => $dominion->round->id,
                'number' => $dominion->round->number,
                'name' => $dominion->round->name,
                'start_date' => $dominion->round->start_date?->toIso8601ZuluString(),
                'end_date' => $dominion->round->end_date?->toIso8601ZuluString(),
                'day' => $dominion->round->isActive() ? $dominion->round->daysInRound() : null,
                'hour' => $dominion->round->isActive() ? $dominion->round->hoursInDay() : null,
                'duration_days' => $dominion->round->durationInDays(),
            ],
            'server_time' => now()->toIso8601ZuluString(),
            'resources' => $this->resourcesPayload($dominion),
            'military' => $this->militaryPayload($dominion),
            'hourly' => $this->hourlyPayload($dominion),
            'population' => $this->populationPayload($dominion),
            'statistics' => $this->statisticsPayload($dominion),
            'links' => [
                'rounds' => route('api.rounds.index'),
                'round_dominions' => route('api.rounds.dominions', $dominion->round),
                'round_realms' => route('api.rounds.realms', $dominion->round),
                'round_events' => route('api.rounds.events', $dominion->round),
            ],
        ]);
    }

    /**
     * Current stockpiles of the key's own dominion.
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
     * Units, spy/wizard strength and OP/DP modifiers of the key's own dominion.
     * Units 1-4 include those returning from invasion, as on the status page;
     * units in training are not included.
     * Modifiers are percentages, as on the Military advisor (+23.5 means x1.235).
     *
     * @return array<string, int|float>
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
     * Hourly figures from the Production advisor for the key's own dominion.
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

    /**
     * Population figures from the Production advisor for the key's own dominion.
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

    public function ops(Request $request): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request, self::DEFAULT_MAX_AGE_HOURS);

        $query = $dominion->realm->infoOps()
            ->with(['targetDominion.race', 'targetDominion.realm'])
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', true);

        if ($maxAgeHours > 0) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        $grouped = $query->orderByDesc('created_at')->get()->groupBy('target_dominion_id');

        $dominions = [];
        foreach ($grouped as $targetId => $infoOps) {
            $target = $infoOps->first()->targetDominion;
            if ($target === null) {
                continue;
            }

            $ops = $this->assembler->assembleForTarget($target, $infoOps);
            if (array_filter($ops) === []) {
                continue;
            }

            $dominions[(string) $targetId] = $this->targetPayload($target, $ops);
        }

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'max_age_hours' => $maxAgeHours,
            'dominions' => (object) $dominions,
        ]);
    }

    public function opsForTarget(Request $request, Dominion $target): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request, 0);

        if ($target->round_id !== $dominion->round_id) {
            return $this->notFound();
        }

        if ($target->realm_id === $dominion->realm_id) {
            return $this->advisorsForTarget($dominion, $target, $maxAgeHours);
        }

        $query = $dominion->realm->infoOps()
            ->where('target_dominion_id', $target->id)
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', true);

        if ($maxAgeHours > 0) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        $infoOps = $query->get();

        if ($infoOps->isEmpty()) {
            return $this->notFound();
        }

        $target->loadMissing(['race', 'realm']);
        $ops = $this->assembler->assembleForTarget($target, $infoOps);

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'max_age_hours' => $maxAgeHours,
            'dominion' => $this->targetPayload($target, $ops),
        ]);
    }

    public function opsForTargetByType(Request $request, Dominion $target, string $type): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request, 0);
        $limit = min(
            self::MAX_HISTORY_LIMIT,
            max(1, (int) $request->query('limit', self::DEFAULT_HISTORY_LIMIT))
        );

        if ($target->round_id !== $dominion->round_id) {
            return $this->notFound();
        }

        if ($target->realm_id === $dominion->realm_id) {
            return response()->json([
                'error' => 'same_realm',
                'message' => 'The Op Archive is not available for dominions in your realm. Use /dominions/me/op-center/{target} for their current data.',
            ], 422);
        }

        if (!$this->assembler->isValidType($type)) {
            return response()->json([
                'error' => 'invalid_parameter',
                'message' => 'The op type must be one of: ' . implode(', ', $this->assembler->getTypes()) . '.',
            ], 422);
        }

        $query = $dominion->realm->infoOps()
            ->where('target_dominion_id', $target->id)
            ->where('type', $type)
            ->orderByDesc('created_at');

        if ($maxAgeHours > 0) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        $target->loadMissing(['race', 'realm']);

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'max_age_hours' => $maxAgeHours,
            'dominion' => [
                'id' => $target->id,
                'name' => $target->name,
                'realm' => $target->realm?->number,
                'race' => $target->race?->name,
            ],
            'type' => $type,
            'ops' => $this->assembler->assembleHistory($target, $type, $query->limit($limit)->get()),
        ]);
    }

    /**
     * Your own dominion and realmies who share their advisors with you are
     * returned with their current data, as on the in-game realm advisors page.
     */
    private function advisorsForTarget(Dominion $dominion, Dominion $target, int $maxAgeHours): JsonResponse
    {
        if (!$dominion->inRealmAndSharesAdvisors($target)) {
            return response()->json([
                'error' => 'advisors_not_shared',
                'message' => 'This dominion has opted not to share their advisors with you.',
            ], 403);
        }

        $target->loadMissing(['race', 'realm']);

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'max_age_hours' => $maxAgeHours,
            'dominion' => $this->targetPayload($target, $this->assembler->assembleFromAdvisors($target)),
        ]);
    }

    private function targetPayload(Dominion $target, array $ops): array
    {
        return [
            'id' => $target->id,
            'name' => $target->name,
            'realm' => $target->realm?->number,
            'race' => $target->race?->name,
            'ops' => $ops,
        ];
    }

    /**
     * Returns 0 when there is no age limit.
     */
    private function resolveMaxAgeHours(Request $request, int $default): int
    {
        if (!$request->has('max_age_hours')) {
            return $default;
        }

        return max(0, (int) $request->query('max_age_hours'));
    }

    private function getApiDominion(): Dominion
    {
        return app('api.dominion');
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'error' => 'not_found',
            'message' => 'No info ops found for this target.',
        ], 404);
    }
}
