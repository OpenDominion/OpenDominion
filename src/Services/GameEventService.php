<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Query\JoinClause;
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
    public const TOWN_CRIER_TYPES = [
        'all' => 'All',
        'all_without_bots' => 'All (Excluding Bot Attacks)',
        'invasions' => 'Invasions',
        'invasions_without_bots' => 'Invasions (Excluding Bot Attacks)',
        'wars' => 'Wars',
        'wonders' => 'Wonders',
        'raids' => 'Raids',
    ];

    public const DEFAULT_TOWN_CRIER_TYPE = 'all';

    public function getTownCrier(Dominion $dominion, ?Realm $realm = null, ?Dominion $target = null, string $type = self::DEFAULT_TOWN_CRIER_TYPE): array
    {
        $showBotAttacks = !in_array($type, ['all_without_bots', 'invasions_without_bots'], true);
        $type = str_replace('_without_bots', '', $type);

        if ($target !== null) {
            return $this->getGameEventsForDominion($target, now(), $type, $showBotAttacks);
        }

        if ($realm !== null) {
            return $this->getGameEventsForRealm($realm, now(), $type, $showBotAttacks);
        }

        return $this->getGameEventsForRound($dominion, now(), $type, $showBotAttacks);
    }

    /**
     * @return array{events: int, botAttacks: int}
     */
    public function getUnreadCounts(Dominion $dominion): array
    {
        $lastSeen = $dominion->town_crier_last_seen ?? $dominion->round->created_at;
        $counts = GameEvent::query()
            ->leftJoin('dominions as source_dominions', function (JoinClause $join): void {
                $join->on('source_dominions.id', '=', 'game_events.source_id')
                    ->where('game_events.source_type', Dominion::class)
                    ->where('game_events.type', 'invasion');
            })
            ->where('game_events.round_id', $dominion->round_id)
            ->where('game_events.created_at', '>', $lastSeen)
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw('COUNT(CASE WHEN source_dominions.user_id IS NULL THEN source_dominions.id END) AS bot_attacks')
            ->toBase()
            ->first();

        return [
            'events' => (int) $counts->total - (int) $counts->bot_attacks,
            'botAttacks' => (int) $counts->bot_attacks,
        ];
    }

    public function getClairvoyance(Realm $realm, Carbon $clairvoyanceCreatedAt): array
    {
        return $this->getGameEventsForRealm($realm, $clairvoyanceCreatedAt);
    }

    private function getGameEventsForRealm(Realm $realm, Carbon $createdBefore, string $type = 'all', bool $showBotAttacks = true): array
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
            ->orderBy('created_at', 'desc');

        $latestEventTime = null;
        if (!$showBotAttacks) {
            $latestEventTime = $this->getLatestEventTime($gameEvents);
            $gameEvents->withoutBotAttacks();
        }

        $gameEvents = $gameEvents->paginate(100)
            ->withQueryString();
        $latestEventTime ??= $gameEvents->max('created_at');

        $this->attachRealmsToGameEvents($gameEvents, $realm->round_id);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' => $gameEvents,
            'latestEventTime' => $latestEventTime,
        ];
    }

    private function getGameEventsForRound(Dominion $dominion, Carbon $createdBefore, string $type = 'all', bool $showBotAttacks = true): array
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
            ->orderBy('created_at', 'desc');

        $latestEventTime = null;
        if (!$showBotAttacks) {
            $latestEventTime = $this->getLatestEventTime($gameEvents);
            $gameEvents->withoutBotAttacks();
        }

        $gameEvents = $gameEvents->paginate(100)
            ->withQueryString();
        $latestEventTime ??= $gameEvents->max('created_at');

        $this->attachRealmsToGameEvents($gameEvents, $dominion->round_id);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' => $gameEvents,
            'latestEventTime' => $latestEventTime,
        ];
    }

    public function getGameEventsForDominion(Dominion $dominion, Carbon $createdBefore, string $type = 'all', bool $showBotAttacks = true): array
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
            ->orderBy('created_at', 'desc');

        $latestEventTime = null;
        if (!$showBotAttacks) {
            $latestEventTime = $this->getLatestEventTime($gameEvents);
            $gameEvents->withoutBotAttacks();
        }

        $gameEvents = $gameEvents->paginate(100)
            ->withQueryString();
        $latestEventTime ??= $gameEvents->max('created_at');

        $this->attachRealmsToGameEvents($gameEvents, $dominion->round_id);

        return [
            'dominionIds' => [(int) $dominion->id],
            'gameEvents' => $gameEvents,
            'latestEventTime' => $latestEventTime,
        ];
    }

    /**
     * Latest event time before bot attacks are filtered out, so hidden bot attacks still count as unread news.
     */
    private function getLatestEventTime(Builder $gameEvents): ?Carbon
    {
        $latestEventTime = (clone $gameEvents)->reorder()->toBase()->max('created_at');

        return $latestEventTime === null ? null : Carbon::parse($latestEventTime);
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
