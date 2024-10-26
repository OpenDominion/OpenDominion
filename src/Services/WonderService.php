<?php

namespace OpenDominion\Services;

use Carbon\Carbon;
use DB;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\GameEvent;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\RoundWonder;
use OpenDominion\Models\Wonder;
use OpenDominion\Services\Dominion\HistoryService;
use OpenDominion\Services\Dominion\QueueService;
use OpenDominion\Services\NotificationService;

class WonderService
{
    public const MAX_WONDERS_PER_REALM = 0.4;

    /**
     * Get the most frequent wonders in the past few rounds for each tier.
     *
     * @return Collection
     */
    public function getFrequentWonders(Round $round)
    {
        $roundIds = $round->league->rounds()->orderByDesc('created_at')->take(10)->pluck('id');

        $tierOneWonderIds = Wonder::active()->tierOne()->pluck('id');
        $tierOneWonders = RoundWonder::with('wonder')
            ->whereIn('round_id', $roundIds)
            ->whereIn('wonder_id', $tierOneWonderIds)
            ->get()
            ->countBy('wonder.key')
            ->sortDesc()
            ->take(5)
            ->keys();

        $tierTwoWonderIds = Wonder::active()->tierTwo()->pluck('id');
        $tierTwoWonders = RoundWonder::with('wonder')
            ->whereIn('round_id', $roundIds)
            ->whereIn('wonder_id', $tierTwoWonderIds)
            ->get()
            ->countBy('wonder.key')
            ->sortDesc()
            ->take(6)
            ->keys();

        return $tierOneWonders->merge($tierTwoWonders);
    }

    /**
     * Get the starting wonders to spawn in the first wave.
     *
     * @return Collection
     */
    public function getStartingWonders(Round $round)
    {
        $tier1 = Wonder::active()->tierOne()->get()->keyBy('key');
        $tier2 = Wonder::active()->tierTwo()->get()->keyBy('key');

        // Remove most frequent wonders
        $frequentWonders = $this->getFrequentWonders($round);
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

        // Remove most frequent wonders
        $frequentWonders = $this->getFrequentWonders($round);
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

    public function handleSentience(Round $round)
    {
        foreach ($round->wonders as $roundWonder) {
            // Not active from Graveyard
            if ($roundWonder->realm_id !== null && $roundWonder->realm->number == 0) {
                continue;
            }
            $wonderPerks = $roundWonder->wonder->perks->pluck('key');
            // Find sentient wonders
            if ($wonderPerks->contains('sentient')) {
                $notificationService = app(NotificationService::class);
                // Get damage dealt today
                $damage = $roundWonder->damage()
                    ->where('created_at', '>', now()->subHours(24))
                    ->get()
                    ->groupBy('realm_id')
                    ->map(function ($realmDamage) {
                        return ['totalDamage' => $realmDamage->sum('damage')];
                    });
                $realmIds = $round->realms->where('number', '!=', 0)->pluck('id');
                foreach ($realmIds as $realmId) {
                    if (!isset($damage[$realmId])) {
                        $damage[$realmId] = ['totalDamage' => 0];
                    }
                }
                $realmIds->forget($roundWonder->realm_id);
                // Select victim realms
                $victimRealmIds = $damage->sortBy('totalDamage')->take(3)->keys();
                foreach ($victimRealmIds as $victimRealmId) {
                    // Select victim dominion
                    $victims = Dominion::where('realm_id', $victimRealmId)->get()->filter(function ($dominion) {
                        return $dominion->isActive() && !$dominion->isLocked();
                    });
                    $dominion = $victims->random();

                    // Remove land and create event
                    DB::transaction(function () use ($dominion, $roundWonder) {
                        $result = $this->handleLandLoss($dominion);

                        GameEvent::create([
                            'round_id' => $roundWonder->round_id,
                            'source_type' => RoundWonder::class,
                            'source_id' => $roundWonder->id,
                            'target_type' => Dominion::class,
                            'target_id' => $dominion->id,
                            'type' => 'wonder_invasion',
                            'data' => $result,
                        ]);

                        $notificationService->queueNotification('wonder_invasion', [
                            'sourceWonderId' => $roundWonder->id,
                            'landLost' => $result['landLost']
                        ]);

                        $notificationService->sendNotifications($dominion, 'irregular_dominion');
                    });
                }
            }
        }
    }

    public function handleLandLoss(Dominion $target)
    {
        $buildingCalculator = app(BuildingCalculator::class);
        $landCalculator = app(LandCalculator::class);
        $queueService = app(QueueService::class);

        // Always treated attacks as 60%
        $landRatio = 0.60;
        $totalLand = $landCalculator->getTotalLand($target);
        $acresLost = (int) ($totalLand / $landRatio) * (0.154 * $landRatio - 0.069) * 0.75;

        $landLossRatio = $acresLost / $totalLand;
        $landAndBuildingsLostPerLandType = $landCalculator->getLandLostByLandType($target, $landLossRatio);

        foreach ($landAndBuildingsLostPerLandType as $landType => $landAndBuildingsLost) {
            $buildingsToDestroy = $landAndBuildingsLost['buildingsToDestroy'];
            $landLost = $landAndBuildingsLost['landLost'];
            $buildingsLostForLandType = $buildingCalculator->getBuildingTypesToDestroy($target, $buildingsToDestroy, $landType);

            // Remove land
            $target->{"land_$landType"} -= $landLost;
            $target->stat_total_land_lost += $landLost;

            // Add discounted land for buildings destroyed
            $target->discounted_land += $buildingsToDestroy;

            // Destroy buildings
            foreach ($buildingsLostForLandType as $buildingType => $buildingsLost) {
                $builtBuildingsToDestroy = $buildingsLost['builtBuildingsToDestroy'];
                $resourceName = "building_{$buildingType}";
                $target->$resourceName -= $builtBuildingsToDestroy;

                $buildingsInQueueToRemove = $buildingsLost['buildingsInQueueToRemove'];

                if ($buildingsInQueueToRemove !== 0) {
                    $queueService->dequeueResource('construction', $target, $resourceName, $buildingsInQueueToRemove);
                }
            }
        }

        $target->save(['event' => HistoryService::EVENT_ACTION_INVADED]);

        return ['landLost' => $acresLost];
    }
}
