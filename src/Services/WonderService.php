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
    public const MAX_WONDERS_PER_REALM = 0.4;

    public const STARTING_WONDERS = ['high_clerics_tower', 'onyx_mausoleum'];

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
        $wonders = Wonder::active()->get()->keyBy('key');
        $existingWonders = RoundWonder::with('wonder')->where('round_id', $round->id)->get()->keyBy('wonder.key');

        if ($existingWonders->count() >= $round->realms()->count() * self::MAX_WONDERS_PER_REALM) {
            return collect();
        }

        if ($round->daysInRound() > 14) {
            $wonders->forget('halls_of_knowledge');
        }

        if ($existingWonders->has('ancient_library') || $existingWonders->has('halls_of_knowledge')) {
            $wonders->forget('ancient_library');
            $wonders->forget('halls_of_knowledge');
        }

        if ($existingWonders->has('ivory_tower') || $existingWonders->has('wizard_academy')) {
            $wonders->forget('ivory_tower');
            $wonders->forget('wizard_academy');
        }

        return $wonders->whereNotIn('id', $existingWonders->pluck('wonder_id')->all());
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
