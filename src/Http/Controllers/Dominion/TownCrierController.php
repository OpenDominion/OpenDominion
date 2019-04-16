<?php

namespace OpenDominion\Http\Controllers\Dominion;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;

class TownCrierController extends AbstractDominionController
{
    public function getIndex()
    {
        $dominion = $this->getSelectedDominion();

        $realm = $dominion->realm;
        $dominionIds = $realm->dominions
            ->pluck('id')
            ->toArray();

        $gameEvents = GameEvent::query()
            ->where('round_id', $dominion->round->id)
            ->where('created_at', '>', now()->subDays(2))
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
            ->get()
            // Filter out unsuccessful invasions against realmies
            ->filter(function (GameEvent $gameEvent) use ($dominionIds) {
                if (
                    ($gameEvent->type === 'invasion') &&
                    ($gameEvent->target_type === Dominion::class) &&
                    in_array($gameEvent->target_id, $dominionIds, true)
                ) {
                    return $gameEvent->data['result']['success'];
                }

                return true;
            });

        return view('pages.dominion.town-crier', compact(
            'gameEvents',
            'realm',
            'dominionIds'
        ));
    }
}
