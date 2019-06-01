<?php


namespace OpenDominion\Services;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

class GameEventService
{
    public function getTownCrier(Dominion $dominion) : array
    {
        $realm = $dominion->realm;

        return $this->getGameEventsForRealm($realm, now());
    }

    public function getClairvoyance(Realm $realm, Carbon $clairvoyanceUpdateAt)
    {
        return $this->getGameEventsForRealm($realm, $clairvoyanceUpdateAt);
    }

    private function getGameEventsForRealm(Realm $realm, Carbon $createdBefore) : array
    {
        $dominionIds = $realm->dominions
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
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
            ->get();

        return [
            'dominionIds' => $dominionIds,
            'gameEvents' =>  $gameEvents
        ];
    }
}