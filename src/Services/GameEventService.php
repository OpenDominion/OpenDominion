<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

class GameEventService
{
    public function getTownCrier(Dominion $dominion, Realm $realm = null) : array
    {
        if ($realm === null) {
            return $this->getGameEventsforRound($dominion, now());
        }

        return $this->getGameEventsForRealm($realm, now());
    }

    public function getGameEventsForDominion(Dominion $dominion) : Collection
    {
        return GameEvent::query()
            ->with(['source', 'target'])
            ->where('source_id', $dominion->id)
            ->orWhere('target_id', $dominion->id)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getClairvoyance(Realm $realm, Carbon $clairvoyanceCreatedAt): array
    {
        return $this->getGameEventsForRealm($realm, $clairvoyanceCreatedAt);
    }

    private function getGameEventsForRealm(Realm $realm, Carbon $createdBefore) : array
    {
        $dominionIds = $realm->dominions
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source', 'target'])
            ->where('round_id', $realm->round->id)
            ->where('created_at', '<', $createdBefore)
            ->where('created_at', '>', now()->subDays(7))
            ->where(function (Builder $query) use ($realm, $dominionIds) {
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
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }

    private function getGameEventsForRound(Dominion $dominion, Carbon $createdBefore) : array
    {
        $dominionIds = $dominion->realm->dominions
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->with(['source', 'target'])
            ->where('round_id', $dominion->round_id)
            ->where('created_at', '<', $createdBefore)
            ->where('created_at', '>', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->paginate(100);

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }
}
