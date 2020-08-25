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
    public const MAX_WONDERS_PER_REALM = 0.5;

    public const STARTING_WONDERS = ['halls_of_knowledge', 'high_clerics_tower'];

    /**
     * Get the starting wonders to spawn in the first wave.
     *
     * @return Collection
     */
    public function getStartingWonders()
    {
        return Wonder::active()->whereIn('key', static::STARTING_WONDERS)->get();
    }

    /**
     * Gets a collection of wonders that are available to spawn.
     *
     * @param Round $round
     * @return Collection
     */
    public function getAvailableWonders(Round $round)
    {
        $existingWonders = RoundWonder::where('round_id', $round->id)->pluck('wonder_id');

        if ($existingWonders->count() >= $round->realms()->count() * self::MAX_WONDERS_PER_REALM) {
            return collect();
        }

        return Wonder::active()->whereNotIn('id', $existingWonders->all())->get();
    }

    /**
     * Creates a new wonder for a given round.
     *
     * @param Round $round
     * @param Wonder $wonder
     */
    public function createWonder(Round $round, Wonder $wonder = null)
    {
        if ($wonder == null) {
            $availableWonders = $this->getAvailableWonders($round);
            if (!$availableWonders->isEmpty()) {
                $wonder = $availableWonders->random();
            }
        }

        if ($wonder !== null) {
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
