<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Round;
use OpenDominion\Services\Dominion\ProtectionService;

class RoundController extends AbstractController
{
    private const DEFAULT_EVENT_LIMIT = 100;
    private const MAX_EVENT_LIMIT = 500;

    public function __construct(
        private LandCalculator $landCalculator,
        private NetworthCalculator $networthCalculator,
        private ProtectionService $protectionService
    ) {
    }

    public function index(): JsonResponse
    {
        $rounds = Round::with('league')
            ->orderByDesc('start_date')
            ->get()
            ->map(fn (Round $round) => $this->roundPayload($round))
            ->values();

        return response()->json($rounds);
    }

    public function dominions(Round $round): JsonResponse
    {
        $dominions = $round->dominions()
            ->with(['realm', 'race'])
            ->where('locked_at', null)
            ->where(function ($query) {
                $query->whereNull('abandoned_at')->orWhere('abandoned_at', '>', now());
            })
            ->get()
            ->map(function ($dominion) {
                return [
                    'id' => $dominion->id,
                    'name' => $dominion->name,
                    'race' => $dominion->race?->name,
                    'realm_number' => $dominion->realm?->number,
                    'realm_name' => $dominion->realm?->name,
                    'land' => $this->landCalculator->getTotalLand($dominion),
                    'networth' => $this->networthCalculator->getDominionNetworth($dominion),
                    'in_protection' => $this->protectionService->isUnderProtection($dominion),
                    'locked' => $dominion->locked_at !== null,
                ];
            })
            ->values();

        return response()->json($dominions);
    }

    public function events(Request $request, Round $round): JsonResponse
    {
        $limit = min(
            self::MAX_EVENT_LIMIT,
            max(1, (int) $request->query('limit', self::DEFAULT_EVENT_LIMIT))
        );

        $since = $this->parseSince($request->query('since'));
        if ($since === false) {
            return response()->json([
                'error' => 'invalid_parameter',
                'message' => 'The "since" parameter must be a valid ISO 8601 timestamp.',
            ], 422);
        }

        $query = GameEvent::query()
            ->where('round_id', $round->id)
            ->orderByDesc('created_at');

        if ($since !== null) {
            $query->where('created_at', '>=', $since);
        }

        $events = $query->limit($limit)->get()->map(function (GameEvent $event) {
            return [
                'id' => $event->id,
                'type' => $event->type,
                'source_type' => $event->source_type,
                'source_id' => $event->source_id,
                'target_type' => $event->target_type,
                'target_id' => $event->target_id,
                'data' => $event->data,
                'created_at' => $event->created_at?->toIso8601String(),
            ];
        })->values();

        return response()->json($events);
    }

    private function roundPayload(Round $round): array
    {
        return [
            'id' => $round->id,
            'number' => $round->number,
            'name' => $round->name,
            'description' => $round->description,
            'league' => $round->league ? [
                'id' => $round->league->id,
                'key' => $round->league->key,
                'description' => $round->league->description,
            ] : null,
            'start_date' => $round->start_date?->toIso8601String(),
            'end_date' => $round->end_date?->toIso8601String(),
            'has_started' => $round->hasStarted(),
            'has_ended' => $round->hasEnded(),
        ];
    }

    /**
     * Returns null when no filter is requested, false when the value is unparseable,
     * or a Carbon instance when valid.
     */
    private function parseSince(?string $value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException $e) {
            return false;
        }
    }
}
