<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Calculators\NetworthCalculator;
use OpenDominion\Calculators\WonderCalculator;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Mappers\GameEventMapper;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Services\Dominion\GovernmentService;
use OpenDominion\Services\Dominion\GuardMembershipService;
use OpenDominion\Services\Dominion\ProtectionService;

class RoundController extends AbstractController
{
    private const DEFAULT_EVENT_LIMIT = 100;
    private const MAX_EVENT_LIMIT = 500;

    public function __construct(
        private LandCalculator $landCalculator,
        private NetworthCalculator $networthCalculator,
        private ProtectionService $protectionService,
        private GameEventMapper $gameEventMapper,
        private GuardMembershipService $guardMembershipService,
        private GovernmentService $governmentService,
        private WonderCalculator $wonderCalculator
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
                    'guard' => $this->guardName($dominion),
                ];
            })
            ->values();

        return response()->json($dominions);
    }

    public function realms(Round $round): JsonResponse
    {
        $realms = $round->realms()
            ->with([
                'warsOutgoing' => fn ($query) => $query->active()->with('targetRealm'),
                'warsIncoming' => fn ($query) => $query->active()->with('sourceRealm'),
            ])
            ->orderBy('number')
            ->get();

        $viewerRealm = $this->getViewerRealm($round, $realms);
        $wondersByRealm = $round->wonders()
            ->with('wonder')
            ->whereNotNull('realm_id')
            ->get()
            ->groupBy('realm_id');

        $payload = $realms->map(function (Realm $realm) use ($viewerRealm, $wondersByRealm) {
            $wars = $realm->warsOutgoing
                ->map(fn (RealmWar $war) => $this->warPayload($war, 'outgoing', $war->targetRealm))
                ->concat($realm->warsIncoming->map(fn (RealmWar $war) => $this->warPayload($war, 'incoming', $war->sourceRealm)))
                ->values();

            return [
                'number' => $realm->number,
                'name' => $realm->name,
                'wonders' => $wondersByRealm->get($realm->id, collect())
                    ->map(fn (RoundWonder $wonder) => $this->wonderPayload($wonder, $realm, $viewerRealm))
                    ->values(),
                'wars' => $wars,
            ];
        })->values();

        return response()->json($payload);
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

        $types = $this->parseEventTypes($request->query('type'));
        if ($types === false) {
            return response()->json([
                'error' => 'invalid_parameter',
                'message' => 'The "type" parameter must be one or more of: ' . implode(', ', GameEventMapper::PUBLIC_TYPES) . '.',
            ], 422);
        }

        $query = GameEvent::query()
            ->with($this->gameEventMapper->getEagerLoads())
            ->where('round_id', $round->id)
            ->whereIn('type', $types)
            ->orderByDesc('created_at');

        if ($since !== null) {
            $query->where('created_at', '>=', $since);
        }

        $events = $query->limit($limit)
            ->get()
            ->map(fn (GameEvent $event) => $this->gameEventMapper->mapPublic($event))
            ->values();

        return response()->json($events);
    }

    /**
     * The API key's realm, when a key for this round was sent. Taken from the
     * already-loaded realms so its wars are available for isAtWar().
     */
    private function getViewerRealm(Round $round, Collection $realms): ?Realm
    {
        if (!app()->bound('api.dominion')) {
            return null;
        }

        $viewer = app('api.dominion');
        if ($viewer->round_id !== $round->id) {
            return null;
        }

        return $realms->firstWhere('id', $viewer->realm_id);
    }

    /**
     * Wonder power as on the in-game wonders page: exact for the holder's own
     * realm and realms at war with the holder, rounded for everyone else.
     *
     * @return array{key: string, name: string, power: int, max_power: int, power_is_approximate: bool}
     */
    private function wonderPayload(RoundWonder $wonder, Realm $holder, ?Realm $viewerRealm): array
    {
        $isExact = $viewerRealm !== null
            && ($viewerRealm->id === $holder->id || $this->governmentService->isAtWar($viewerRealm, $holder));

        $power = $isExact
            ? $this->wonderCalculator->getCurrentPower($wonder)
            : $this->wonderCalculator->getApproximatePower($wonder);

        return [
            'key' => $wonder->wonder->key,
            'name' => $wonder->wonder->name,
            'power' => (int) round($power),
            'max_power' => (int) $wonder->power,
            'power_is_approximate' => !$isExact,
        ];
    }

    /**
     * Matches the guard icon shown for every dominion on the in-game search page.
     * Black Guard is left out: its icon is only public when the player opts in.
     */
    private function guardName(Dominion $dominion): ?string
    {
        if ($this->guardMembershipService->isEliteGuardMember($dominion)) {
            return 'elite';
        }

        if ($this->guardMembershipService->isRoyalGuardMember($dominion)) {
            return 'royal';
        }

        return null;
    }

    /**
     * A current war as shown on the in-game realm page, from one realm's side.
     *
     * @return array{direction: string, realm_number: int|null, realm_name: string|null, status: string, declared_at: string|null, active_at: string|null, inactive_at: string|null}
     */
    private function warPayload(RealmWar $war, string $direction, ?Realm $otherRealm): array
    {
        if ($war->inactive_at !== null) {
            $status = 'expiring';
        } elseif ($this->governmentService->getHoursBeforeWarActive($war) === 0) {
            $status = 'active';
        } else {
            $status = 'pending';
        }

        return [
            'direction' => $direction,
            'realm_number' => $otherRealm?->number,
            'realm_name' => $otherRealm?->name,
            'status' => $status,
            'declared_at' => $war->created_at?->copy()->startOfHour()->toIso8601ZuluString(),
            'active_at' => $war->active_at?->toIso8601ZuluString(),
            'inactive_at' => $war->inactive_at?->toIso8601ZuluString(),
        ];
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
            'start_date' => $round->start_date?->toIso8601ZuluString(),
            'end_date' => $round->end_date?->toIso8601ZuluString(),
            'has_started' => $round->hasStarted(),
            'has_ended' => $round->hasEnded(),
        ];
    }

    /**
     * Returns every public event type when no filter is requested, false when
     * any requested type is not public, or the requested types when valid.
     * Accepts a comma-separated list.
     *
     * @return string[]|false
     */
    private function parseEventTypes(mixed $value): array|false
    {
        if ($value === null || $value === '') {
            return GameEventMapper::PUBLIC_TYPES;
        }

        if (!is_string($value)) {
            return false;
        }

        $types = array_values(array_unique(array_map('trim', explode(',', $value))));

        if (array_diff($types, GameEventMapper::PUBLIC_TYPES) !== []) {
            return false;
        }

        return $types;
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
