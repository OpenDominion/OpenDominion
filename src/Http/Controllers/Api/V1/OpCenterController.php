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
                'ends_at' => $dominion->round->end_date?->toIso8601String(),
            ],
        ]);
    }

    public function ops(Request $request): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request);

        $query = $dominion->realm->infoOps()
            ->with(['targetDominion.race', 'targetDominion.realm'])
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', true);

        if ($maxAgeHours !== null) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        $grouped = $query->orderByDesc('created_at')->get()->groupBy('target_dominion_id');

        $targets = [];
        foreach ($grouped as $targetId => $infoOps) {
            $target = $infoOps->first()->targetDominion;
            if ($target === null) {
                continue;
            }

            $ops = $this->assembler->assembleForTarget($target, $infoOps);
            if (empty($ops)) {
                continue;
            }

            $targets[(string) $targetId] = $this->targetPayload($target, $ops);
        }

        return response()->json([
            'dominion' => [
                'id' => $dominion->id,
                'realm' => $dominion->realm->number,
                'round_id' => $dominion->round_id,
            ],
            'generated_at' => now()->toIso8601String(),
            'max_age_hours' => $maxAgeHours,
            'targets' => (object) $targets,
        ]);
    }

    public function opsForTarget(Request $request, Dominion $target): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request);

        if ($target->round_id !== $dominion->round_id) {
            return $this->notFound();
        }

        $query = $dominion->realm->infoOps()
            ->where('target_dominion_id', $target->id)
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', true);

        if ($maxAgeHours !== null) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        $infoOps = $query->get();

        if ($infoOps->isEmpty()) {
            return $this->notFound();
        }

        $target->loadMissing(['race', 'realm']);
        $ops = $this->assembler->assembleForTarget($target, $infoOps);

        return response()->json([
            'dominion' => [
                'id' => $dominion->id,
                'realm' => $dominion->realm->number,
                'round_id' => $dominion->round_id,
            ],
            'generated_at' => now()->toIso8601String(),
            'max_age_hours' => $maxAgeHours,
            'target' => $this->targetPayload($target, $ops),
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

    private function resolveMaxAgeHours(Request $request): ?int
    {
        if (!$request->has('max_age_hours')) {
            return self::DEFAULT_MAX_AGE_HOURS;
        }

        $value = $request->query('max_age_hours');
        if ($value === '' || $value === '0' || $value === null) {
            return null;
        }

        return max(1, (int) $value);
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
