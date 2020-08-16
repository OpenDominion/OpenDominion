<?php

namespace OpenDominion\Services;

use Carbon\Carbon;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;

class WonderService
{
    public const MAX_WONDERS = 10;

    /**
     * Gets a collection of wonders that are available to spawn.
     *
     * @param Round $round
     * @return Collection
     */
    public function getAvailableWonders(Round $round)
    {
        $existingWonders = RoundWonder::where('round_id', $round->id)->pluck('wonder_id');

        if ($existingWonders->count() >= self::MAX_WONDERS) {
            return collect();
        }

        return Wonder::active()->whereNotIn('id', $existingWonders->all())->get();
    }

    /**
     * Creates a new wonder for a given round.
     *
     * @param Round $round
     */
    public function createWonder(Round $round)
    {
        $availableWonders = $this->getAvailableWonders($round);

        if (!$availableWonders->isEmpty()) {
            $wonder = $availableWonders->random();

            $roundWonder = RoundWonder::create([
                'round_id' => $round->id,
                'realm_id' => null,
                'wonder_id' => $wonder->id,
                'power' => $wonder->power
            ]);

            GameEvent::create([
                'round_id' => $round->id,
                'source_type' => Wonder::class,
                'source_id' => $wonder->id,
                'target_type' => Wonder::class,
                'target_id' => $wonder->id,
                'type' => 'wonder_spawned',
                'data' => ['power' => $wonder->power],
            ]);
        }
    }
}
