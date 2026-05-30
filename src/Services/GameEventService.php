<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\RaidObjectiveTactic;
use OpenDominion\Models\Realm;
use OpenDominion\Models\RealmWar;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;

class GameEventService
{
    public function getTownCrier(Dominion $dominion, Realm $realm = null, Dominion $target = null, string $type = 'all'): array
    {
        if ($target !== null) {
            return $this->getGameEventsForDominion($target, now(), $type);
        }

        if ($realm !== null) {
            return $this->getGameEventsForRealm($realm, now(), $type);
        }

        return $this->getGameEventsforRound($dominion, now(), $type);
    }

    public function getClairvoyance(Realm $realm, Carbon $clairvoyanceCreatedAt): array
    {
        return $this->getGameEventsForRealm($realm, $clairvoyanceCreatedAt);
    }

    private function getGameEventsForRealm(Realm $realm, Carbon $createdBefore, string $type = 'all'): array
    {
        $dominionIds = Dominion::where('realm_id', $realm->id)
            ->pluck('id')
            ->toArray();

        $realmWarIds = RealmWar::where('target_realm_id', $realm->id)
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    Realm::class => [],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->with(['target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    RaidObjectiveTactic::class => [],
                    Realm::class => [],
                    RealmWar::class => [],
                    RoundWonder::class => ['wonder'],
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
            ->where('round_id', $realm->round_id)
            ->where('created_at', '<', $createdBefore)
            ->where(function ($query) use ($type) {
                if ($type == 'invasions') {
                    $query->whereIn('type', ['invasion']);
                } elseif ($type == 'wars') {
                    $query->whereIn('type', ['war_declared', 'war_canceled']);
                } elseif ($type == 'wonders') {
                    $query->whereIn('type', ['wonder_attacked', 'wonder_destroyed', 'wonder_spawned']);
                } elseif ($type == 'raids') {
                    $query->whereIn('type', ['raid_attacked']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $this->attachRealmsToGameEvents($gameEvents, $realm->round_id);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }

    private function getGameEventsForRound(Dominion $dominion, Carbon $createdBefore, string $type = 'all'): array
    {
        $dominionIds = Dominion::where('realm_id', $dominion->realm_id)
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    Realm::class => [],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->with(['target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    RaidObjectiveTactic::class => [],
                    Realm::class => [],
                    RealmWar::class => [],
                    RoundWonder::class => ['wonder'],
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
                } elseif ($type == 'raids') {
                    $query->whereIn('type', ['raid_attacked']);
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $this->attachRealmsToGameEvents($gameEvents, $dominion->round_id);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }

    public function getGameEventsForDominion(Dominion $dominion, Carbon $createdBefore, string $type = 'all'): array
    {
        $gameEvents = GameEvent::query()
            ->with(['source' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    Realm::class => [],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->with(['target' => function (MorphTo $morphTo) {
                $morphTo->morphWith([
                    Dominion::class => ['race'],
                    RaidObjectiveTactic::class => [],
                    Realm::class => [],
                    RealmWar::class => [],
                    RoundWonder::class => ['wonder'],
                ]);
            }])
            ->where(function (Builder $query) use ($dominion) {
                $query
                    ->orWhere(function (Builder $query) use ($dominion) {
                        $query->where('source_id', $dominion->id);
                        $query->where('source_type', Dominion::class);
                    })
                    ->orWhere(function (Builder $query) use ($dominion) {
                        $query->where('target_id', $dominion->id);
                        $query->where('target_type', Dominion::class);
                    });
            })
            ->where('round_id', $dominion->round_id)
            ->where('created_at', '<', $createdBefore)
            ->where(function ($query) use ($type) {
                if ($type == 'invasions') {
                    $query->where('type', 'invasion');
                } elseif ($type == 'wonders') {
                    $query->where('type', 'wonder_attacked');
                } elseif ($type == 'raids') {
                    $query->where('type', 'raid_attacked');
                }
            })
            ->orderBy('created_at', 'desc')
            ->paginate(100)
            ->withQueryString();

        $this->attachRealmsToGameEvents($gameEvents, $dominion->round_id);

        return [
            'dominionIds' => [(int) $dominion->id],
            'gameEvents' =>  $gameEvents
        ];
    }

    private function attachRealmsToGameEvents($gameEvents, int $roundId): void
    {
        if ($gameEvents->isEmpty()) {
            return;
        }

        $realms = Realm::where('round_id', $roundId)->get()->keyBy('id');

        foreach ($gameEvents as $event) {
            $this->attachRealmRelations($event->source, $realms);
            $this->attachRealmRelations($event->target, $realms);
        }
    }

    private function attachRealmRelations($model, $realms): void
    {
        if ($model === null) {
            return;
        }

        if ($model instanceof Dominion || $model instanceof RoundWonder) {
            if ($model->realm_id !== null) {
                $model->setRelation('realm', $realms->get($model->realm_id));
            }
        } elseif ($model instanceof RealmWar) {
            $model->setRelation('sourceRealm', $realms->get($model->source_realm_id));
            $model->setRelation('targetRealm', $realms->get($model->target_realm_id));
        }
    }
}
