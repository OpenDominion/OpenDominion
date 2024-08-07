<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;

class GameEventService
{
    public function getTownCrier(Dominion $dominion, Realm $realm = null, string $type = 'all'): array
    {
        if ($realm === null) {
            return $this->getGameEventsforRound($dominion, now(), $type);
        }

        return $this->getGameEventsForRealm($realm, now(), $type);
    }

    public function getGameEventsForDominion(Dominion $dominion): Collection
    {
        return GameEvent::query()
            ->with(['source', 'target'])
            ->where(function ($query) use ($dominion) {
                $query->where('source_id', $dominion->id);
                $query->where('source_type', Dominion::class);
            })
            ->orWhere(function ($query) use ($dominion) {
                $query->where('target_id', $dominion->id);
                $query->where('target_type', Dominion::class);
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLatestInvasionEventsForDominion(Dominion $dominion, int $count): Collection
    {
        return GameEvent::query()
            ->with(['source', 'target'])
            ->where(function ($query) use ($dominion) {
                $query->where('type', 'invasion');
                $query->where('source_id', $dominion->id);
                $query->where('source_type', Dominion::class);
            })
            ->orWhere(function ($query) use ($dominion) {
                $query->where('type', 'invasion');
                $query->where('target_id', $dominion->id);
                $query->where('target_type', Dominion::class);
            })
            ->orderBy('created_at', 'desc')
            ->take($count)
            ->get();
    }

    public function getClairvoyance(Realm $realm, Carbon $clairvoyanceCreatedAt): array
    {
        return $this->getGameEventsForRealm($realm, $clairvoyanceCreatedAt);
    }

    private function getGameEventsForRealm(Realm $realm, Carbon $createdBefore, string $type = 'all'): array
    {
        $dominionIds = $realm->dominions
            ->pluck('id')
            ->toArray();

        $realmWarIds = RealmWar::where('target_realm_id', $realm->id)
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race', 'realm'],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->with(['target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race', 'realm'],
                    RealmWar::class => ['sourceRealm', 'targetRealm'],
                    RoundWonder::class => ['realm', 'wonder'],
                ]);
            }])
            ->where(function (Builder $query) use ($realm, $dominionIds, $realmWarIds) {
                $query
                    ->orWhere(function (Builder $query) use ($dominionIds) {
                        $query->where('source_type', Dominion::class)
                            ->whereIn('source_id', $dominionIds);
                    })
                    ->orWhere(function (Builder $query) use ($dominionIds) {
                        $query->where('target_type', Dominion::class)
                            ->whereIn('target_id', $dominionIds);
                    })
                    ->orWhere(function (Builder $query) use ($realm) {
                        $query->where('source_type', Realm::class)
                            ->where('source_id', $realm->id);
                    })
                    ->orWhere(function (Builder $query) use ($realm) {
                        $query->where('target_type', Realm::class)
                            ->where('target_id', $realm->id);
                    })
                    ->orWhere(function (Builder $query) use ($realmWarIds) {
                        $query->where('target_type', RealmWar::class)
                            ->whereIn('target_id', $realmWarIds);
                    });
            })
            ->where('round_id', $realm->round->id)
            ->where('created_at', '<', $createdBefore)
            ->where(function ($query) use ($type) {
                if ($type == 'invasions') {
                    $query->whereIn('type', ['invasion']);
                } elseif ($type == 'wars') {
                    $query->whereIn('type', ['war_declared', 'war_canceled']);
                } elseif ($type == 'wonders') {
                    $query->whereIn('type', ['wonder_attacked', 'wonder_destroyed', 'wonder_spawned']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('type', 'desc')
            ->paginate(100)
            ->withQueryString();

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }

    private function getGameEventsForRound(Dominion $dominion, Carbon $createdBefore, string $type = 'all'): array
    {
        $dominionIds = $dominion->realm->dominions
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race', 'realm'],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->with(['target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race', 'realm'],
                    RealmWar::class => ['sourceRealm', 'targetRealm'],
                    RoundWonder::class => ['realm', 'wonder'],
                ]);
            }])
            ->where('round_id', $dominion->round_id)
            ->where('created_at', '<', $createdBefore)
            ->where(function ($query) use ($type) {
                if ($type == 'invasions') {
                    $query->whereIn('type', ['invasion']);
                } elseif ($type == 'wars') {
                    $query->whereIn('type', ['war_declared', 'war_canceled']);
                } elseif ($type == 'wonders') {
                    $query->whereIn('type', ['wonder_attacked', 'wonder_destroyed', 'wonder_spawned']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('type', 'desc')
            ->paginate(100)
            ->withQueryString();

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }
}
