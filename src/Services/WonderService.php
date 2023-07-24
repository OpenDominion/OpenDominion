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
    public const MAX_WONDERS_PER_REALM = 1;

    /**
     * Get the starting wonders to spawn in the first wave.
     *
     * @return Collection
     */
    public function getStartingWonders(Round $round)
    {
        $tier1 = Wonder::active()->tierOne()->get()->keyBy('key');
        $tier2 = Wonder::active()->tierTwo()->get()->keyBy('key');

        // TODO: De-duplicate later
        // Remove most frequent wonders
        $roundIds = $round->league->rounds->pluck('id');
        $frequentWonders = RoundWonder::with('wonder')
            ->whereIn('round_id', $roundIds)
            ->get()
            ->where('wonder.active', true)
            ->countBy('wonder.key')
            ->sortDesc()
            ->take(5)
            ->keys();
        foreach ($frequentWonders as $wonderKey) {
            $tier1->forget($wonderKey);
            $tier2->forget($wonderKey);
        }

        $wonderOptions = $tier1->random(3);
        if ($wonderOptions->has('halls_of_knowledge')) {
            $wonderOptions->forget('ancient_library');
        }
        if ($wonderOptions->has('ancient_library')) {
            $wonderOptions->forget('halls_of_knowledge');
        }

        // Spawn two Tier 1 and one Tier 2
        return $wonderOptions->random(2)->merge($tier2->random(1));
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

        // TODO: De-duplicate later
        // Remove most frequent wonders
        $roundIds = $round->league->rounds->pluck('id');
        $frequentWonders = RoundWonder::with('wonder')
            ->whereIn('round_id', $roundIds)
            ->get()
            ->where('wonder.active', true)
            ->countBy('wonder.key')
            ->sortDesc()
            ->take(5)
            ->keys();
        foreach ($frequentWonders as $wonderKey) {
            $wonders->forget($wonderKey);
        }

        // Limit to three Tier 1
        if ($existingWonders->where('wonder.power', Wonder::TIER_ONE_POWER)->count() >= 3) {
            $wonders = $wonders->where('power', Wonder::TIER_TWO_POWER);
        }

        if ($existingWonders->count() >= $round->realms()->count() * self::MAX_WONDERS_PER_REALM) {
            return collect();
        }

        if ($round->daysInRound() > 9) {
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
                'created_at' => now()->startOfHour()
            ]);
        }
    }
}
