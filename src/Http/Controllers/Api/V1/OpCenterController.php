<?php

namespace OpenDominion\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenDominion\Http\Controllers\AbstractController;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\InfoOp;
use OpenDominion\Models\Realm;
use OpenDominion\Services\Dominion\InfoOpAssemblerService;
use OpenDominion\Services\Dominion\ProtectionService;

class OpCenterController extends AbstractController
{
    private const DEFAULT_MAX_AGE_HOURS = 12;
    private const DEFAULT_HISTORY_LIMIT = 100;
    private const MAX_HISTORY_LIMIT = 500;

    public function __construct(
        private InfoOpAssemblerService $assembler,
        private ProtectionService $protectionService
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
            'links' => [
                'advisors' => route('api.dominions.advisors'),
                'op_center' => route('api.dominions.op-center'),
                'rounds' => route('api.rounds.index'),
                'round_dominions' => route('api.rounds.dominions', $dominion->round),
                'round_events' => route('api.rounds.events', $dominion->round),
                'round_realms' => route('api.rounds.realms', $dominion->round),
            ],
        ]);
    }

    public function ops(Request $request): JsonResponse
    {
        $dominion = $this->getApiDominion();
        $maxAgeHours = $this->resolveMaxAgeHours($request, self::DEFAULT_MAX_AGE_HOURS);

        if ($this->isBlockedByProtection($dominion)) {
            return $this->underProtection();
        }

        $realmNumber = $request->query('realm');
        $realm = null;
        if ($realmNumber !== null) {
            $realm = ctype_digit((string) $realmNumber)
                ? Realm::where('round_id', $dominion->round_id)->where('number', (int) $realmNumber)->first()
                : null;

            if ($realm === null) {
                return response()->json([
                    'error' => 'invalid_parameter',
                    'message' => 'The "realm" parameter must be the number of a realm in your round.',
                ], 422);
            }
        }

        $query = $dominion->realm->infoOps()
            ->with(['targetDominion.race', 'targetDominion.realm'])
            ->where('type', '!=', 'clairvoyance')
            ->where('latest', true);

        if ($maxAgeHours > 0) {
            $query->where('created_at', '>=', now()->subHours($maxAgeHours));
        }

        if ($realm !== null) {
            $query->whereIn('target_dominion_id', Dominion::where('realm_id', $realm->id)->select('id'));
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
            'realm' => $realm?->number,
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
            return $this->sameRealm();
        }

        if ($this->isBlockedByProtection($dominion)) {
            return $this->underProtection();
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
            return $this->sameRealm();
        }

        if ($this->isBlockedByProtection($dominion)) {
            return $this->underProtection();
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

    public function history(Request $request): JsonResponse
    {
        $dominion = $this->getApiDominion();

        if ($this->isBlockedByProtection($dominion)) {
            return $this->underProtection();
        }

        $filters = $this->historyFilters($request, $dominion);
        if ($filters instanceof JsonResponse) {
            return $filters;
        }

        $infoOps = $this->historyQuery($dominion, $filters)->limit($filters['limit'])->get();

        return response()->json([
            'generated_at' => now()->toIso8601ZuluString(),
            'dominion' => $filters['dominion']?->id,
            'realm' => $filters['realm']?->number,
            'type' => $filters['type'],
            'since' => $filters['since']?->toIso8601ZuluString(),
            'until' => $filters['until']?->toIso8601ZuluString(),
            'ops' => $infoOps->map(fn (InfoOp $infoOp) => $this->historyOpPayload($infoOp))->filter()->values()->all(),
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

    /**
     * Dominions in protection cannot use the op center once the round has started,
     * matching the in-game op center.
     */
    private function isBlockedByProtection(Dominion $dominion): bool
    {
        return $this->protectionService->isUnderProtection($dominion) && $dominion->round->hasStarted();
    }

    private function underProtection(): JsonResponse
    {
        return response()->json([
            'error' => 'under_protection',
            'message' => 'Dominions in protection are not allowed access to the op center.',
        ], 403);
    }

    /**
     * Op center endpoints only serve info ops, which are never gathered on your own realm.
     */
    private function sameRealm(): JsonResponse
    {
        return response()->json([
            'error' => 'same_realm',
            'message' => 'The op center is not available for dominions in your realm. Use /dominions/me/advisors for their current data.',
        ], 422);
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

    /**
     * The op history's query parameters, validated, or the 422 to return.
     *
     * @return array{dominion: ?Dominion, realm: ?Realm, type: ?string, since: ?Carbon, until: ?Carbon,
     *               limit: int}|JsonResponse
     */
    private function historyFilters(Request $request, Dominion $dominion): array|JsonResponse
    {
        $target = $this->findDominionInRound($dominion, $request->query('dominion'));
        if ($target === false) {
            return $this->invalidParameter('The "dominion" parameter must be the ID of a dominion in your round.');
        }

        $realm = $this->findRealmInRound($dominion, $request->query('realm'));
        if ($realm === false) {
            return $this->invalidParameter('The "realm" parameter must be the number of a realm in your round.');
        }

        if ($target !== null && $realm !== null) {
            return $this->invalidParameter('Use either "dominion" or "realm", not both.');
        }

        $type = $request->query('type');
        if ($type !== null && !$this->assembler->isValidType((string) $type)) {
            return $this->invalidParameter(
                'The op type must be one of: ' . implode(', ', $this->assembler->getTypes()) . '.'
            );
        }

        $since = $this->parseTimestamp($request->query('since'));
        $until = $this->parseTimestamp($request->query('until'));
        if ($since === false || $until === false) {
            return $this->invalidParameter('The "since" and "until" parameters must be valid ISO 8601 timestamps.');
        }

        $asksForOwnRealm = ($target?->realm_id ?? $realm?->id) === $dominion->realm_id;
        if ($asksForOwnRealm && !$this->roundIsOver($dominion)) {
            return $this->sameRealm();
        }

        return [
            'dominion' => $target,
            'realm' => $realm,
            'type' => $type,
            'since' => $since,
            'until' => $until,
            'limit' => min(
                self::MAX_HISTORY_LIMIT,
                max(1, (int) $request->query('limit', self::DEFAULT_HISTORY_LIMIT))
            ),
        ];
    }

    private function historyQuery(Dominion $dominion, array $filters): Builder
    {
        $query = InfoOp::query()
            ->with(['targetDominion.race', 'targetDominion.realm', 'sourceDominion.realm'])
            ->where('type', '!=', 'clairvoyance')
            ->where(function (Builder $visible) use ($dominion) {
                $visible->where('source_realm_id', $dominion->realm_id);

                if ($this->roundIsOver($dominion)) {
                    $visible->orWhereIn('target_dominion_id', $this->dominionIdsIn($dominion->realm));
                }
            })
            ->orderByDesc('created_at');

        if ($filters['dominion'] !== null) {
            $query->where('target_dominion_id', $filters['dominion']->id);
        }

        if ($filters['realm'] !== null) {
            $query->whereIn('target_dominion_id', $this->dominionIdsIn($filters['realm']));
        }

        if ($filters['type'] !== null) {
            $query->where('type', $filters['type']);
        }

        if ($filters['since'] !== null) {
            $query->where('created_at', '>=', $filters['since']);
        }

        if ($filters['until'] !== null) {
            $query->where('created_at', '<=', $filters['until']);
        }

        return $query;
    }

    /**
     * One op of the history: its type, its target, who took it (the archive's "Revealed ... by"),
     * and the same fields the Op Archive returns for that type. Null for an op whose target is gone.
     */
    private function historyOpPayload(InfoOp $infoOp): ?array
    {
        $target = $infoOp->targetDominion;
        if ($target === null) {
            return null;
        }

        $fields = $this->assembler->assembleHistory($target, $infoOp->type, collect([$infoOp]));
        $source = $infoOp->sourceDominion;

        return [
            'type' => $infoOp->type,
            'target' => [
                'id' => $target->id,
                'name' => $target->name,
                'realm' => $target->realm?->number,
                'race' => $target->race?->name,
            ],
            'source' => [
                'id' => $infoOp->source_dominion_id,
                'name' => $source?->name,
                'realm' => $source?->realm?->number,
            ],
        ] + ($fields[0] ?? []);
    }

    private function dominionIdsIn(Realm $realm): Builder
    {
        return Dominion::where('realm_id', $realm->id)->select('id');
    }

    /**
     * Once the round has officially ended the op history includes the ops taken on your own
     * realm, as the in-game op center archive does after the round. Never before.
     */
    private function roundIsOver(Dominion $dominion): bool
    {
        return $dominion->round->hasEnded();
    }

    /**
     * The dominion with this ID in the API dominion's round; null when none was asked for,
     * false when the ID is not a dominion in that round.
     */
    private function findDominionInRound(Dominion $dominion, mixed $id): Dominion|false|null
    {
        if ($id === null) {
            return null;
        }

        $target = ctype_digit((string) $id)
            ? Dominion::where('round_id', $dominion->round_id)->find((int) $id)
            : null;

        return $target ?? false;
    }

    /**
     * The realm with this number in the API dominion's round; null when none was asked for,
     * false when the number is not a realm in that round.
     */
    private function findRealmInRound(Dominion $dominion, mixed $number): Realm|false|null
    {
        if ($number === null) {
            return null;
        }

        $realm = ctype_digit((string) $number)
            ? Realm::where('round_id', $dominion->round_id)->where('number', (int) $number)->first()
            : null;

        return $realm ?? false;
    }

    /**
     * An optional ISO 8601 timestamp: null when absent, false when it cannot be read (as the
     * events endpoint parses `since`).
     */
    private function parseTimestamp(mixed $value): Carbon|false|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_string($value)) {
            return false;
        }

        try {
            return Carbon::parse($value);
        } catch (InvalidFormatException $e) {
            return false;
        }
    }

    private function invalidParameter(string $message): JsonResponse
    {
        return response()->json([
            'error' => 'invalid_parameter',
            'message' => $message,
        ], 422);
    }
}
