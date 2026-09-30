<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Services\Dominion\InfoOpAssemblerService;

class OpCenterController extends AbstractController
{
    private const DEFAULT_MAX_AGE_HOURS = 12;
    private const DEFAULT_HISTORY_LIMIT = 100;
    private const MAX_HISTORY_LIMIT = 500;

    public function __construct(private InfoOpAssemblerService $assembler)
    {
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
            'links' => [
                'rounds' => route('api.rounds.index'),
                'round_dominions' => route('api.rounds.dominions', $dominion->round),
                'round_realms' => route('api.rounds.realms', $dominion->round),
                'round_events' => route('api.rounds.events', $dominion->round),
            ],
        ]);
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
