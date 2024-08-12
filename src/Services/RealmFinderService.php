<?php

namespace OpenDominion\Services;

use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
use OpenDominion\Models\User;
use OpenDominion\Services\NotificationService;

class RealmFinderService
{
    /**
     * @var int Maximum number of packs that can exist in a single realm
     */
    protected const MAX_PACKS_PER_REALM = 3;

    /**
     * @var int Maximum number of players allowed in packs in a single realm
     */
    protected const MAX_PACKED_PLAYERS_PER_REALM = 8;

    /**
     * @var int Number of hours before round start to begin realm assignment
     */
    public const ASSIGNMENT_HOURS_BEFORE_START = 96;

    /**
     * @var int Minimum number of realms to create
     */
    public const ASSIGNMENT_MIN_REALM_COUNT = 8;

    /**
     * @var int Maximum number of realms to create
     */
    public const ASSIGNMENT_MAX_REALM_COUNT = 14;

    /**
     * Finds and returns the first best realm for a new Dominion to settle in.
     *
     * @param Round $round
     * @param Race $race
     * @param int $slotsNeeded
     * @param bool $forPack
     *
     * @return Realm|null
     * @see DominionFactory::create()
     */
    public function findRealm(Round $round, Race $race, User $user, int $slotsNeeded = 1, bool $forPack = false): ?Realm
    {
        if (now() < $round->start_date->copy()->subHours(static::ASSIGNMENT_HOURS_BEFORE_START)) {
            return $round->realms()->where('number', 0)->first();
        }

        // Get a list of realms which are not full, disregarding pack status for now
        $realmQuery = Realm::active()
            ->with('packs.dominions')
            ->where('number', '!=', 0)
            ->where('round_id', $round->id);

        if (!$round->mixed_alignment) {
            $realmQuery = $realmQuery->where(['realms.alignment' => $race->alignment]);
        }

        $realms = $realmQuery->groupBy('realms.id')
            ->get()
            ->filter(static function ($realm) use ($round, $slotsNeeded, $forPack) {
                // Check pack status
                if ($forPack) {
                    // Don't create realms after assignment
                    if ($round->hasAssignedRealms()) {
                        return true;
                    }
                    if (static::MAX_PACKS_PER_REALM !== null) {
                        // Reached maximum number of packs
                        if ($realm->packs->count() >= static::MAX_PACKS_PER_REALM) {
                            return false;
                        }
                        // Check if multiple packs would exceed the per realm max
                        if (($realm->totalPackSize() + $slotsNeeded) > static::MAX_PACKED_PLAYERS_PER_REALM) {
                            return false;
                        }
                    }
                }
                return true;
            });

        if ($realms->count() == 0) {
            return null;
        }

        // Assign new players to the smallest realms
        $smallestRealmSize = $realms->map(function ($realm) {
            return $realm->sizeAllocated();
        })->min();
        $smallestRealmCount = $realms->filter(function ($realm) use ($smallestRealmSize) {
            if ($realm->sizeAllocated() == $smallestRealmSize) {
                return true;
            }
        })->count();
        // Select minimum number of smallest realms
        $realmsBySize = $realms->sortBy(function ($realm) {
            return $realm->sizeAllocated();
        })->take(max($smallestRealmCount, 3));
        if ($user->rating == 0) {
            return $realmsBySize->first();
        }
        // Calculate ratings for available realms
        $realmRatings = $realmsBySize->map(function ($realm) {
            return [
                'id' => $realm->id,
                'rating' => $this->calculateRating($realm->dominions()->with('user')->get()->map(function ($dominion) {
                    return ['rating' => $dominion->user->rating];
                })->toArray())
            ];
        });
        if ($user->rating > $realmRatings->avg('rating')) {
            $realm = $realmRatings->sortBy('rating')->first();
        } else {
            $realm = $realmRatings->sortByDesc('rating')->first();
        }
        return $realms->find($realm['id']);
    }

    /**
     * Recalculate the rating of an array of players
     *
     * @param array $players
     */
    public function calculateRating(array $players)
    {
        $ratings = collect($players)->map(function ($player) {
            return $player['rating'];
        })->toArray();
        return root_mean_square($ratings);
    }

    /**
     * Assigns all registered dominions (in realm 0) to newly created realms
     *
     * The number of dominions that can exist in a realm is dictated by
     * $round->realm_size.
     *
     * @param Round $round
     */
    public function assignRealms(Round $round)
    {
        // Close open packs and remove solo players
        $packs = Pack::where('round_id', $round->id)->get();
        foreach ($packs as $pack) {
            $pack->close();
            if ($pack->dominions()->count() == 1) {
                $pack->dominions()->update(['pack_id' => null]);
            }
        }

        // Fetch all registered dominions
        $registeredDominions = $round->activeDominions()
            ->where('user_id', '!=', null)
            ->where('realms.number', 0)
            ->get();

        // Collect data for all dominions
        $allPlayers = collect();
        foreach ($registeredDominions as $dominion) {
            $allPlayers = $allPlayers->push([
                'dominion_id' => $dominion->id,
                'pack_id' => $dominion->pack_id,
                'user_id' => $dominion->user_id,
                'rating' => $dominion->user->rating
            ]);
        }

        // Calculations to be used later
        $averageRating = $allPlayers->where('rating', '!=', 0)->avg('rating') - 100;

        // Separate packed players
        $packs = [];
        foreach ($allPlayers->where('pack_id', '!=', null) as $player) {
            if ($player['rating'] == 0) {
                $player['rating'] = $averageRating;
            }
            if (isset($packs[$player['pack_id']])) {
                $packs[$player['pack_id']]['players'][] = $player;
                $packs[$player['pack_id']]['size']++;
            } else {
                $packs[$player['pack_id']] = ['players' => [$player], 'rating' => '0', 'size' => 1];
            }
            $packs[$player['pack_id']]['rating'] = $this->calculateRating($packs[$player['pack_id']]['players']);
        }

        // Classify packs by size
        $largePacks = collect();
        $smallPacks = collect();
        foreach ($packs as $packId => $pack) {
            if ($pack['size'] > 3) {
                $largePacks[$packId] = $pack;
            } else {
                $smallPacks[$packId] = $pack;
            }
            // Update rating
            Pack::where('id', $packId)->update(['rating' => $pack['rating']]);
        }

        // 'Promote' smaller packs
        if ($largePacks->count() < static::ASSIGNMENT_MIN_REALM_COUNT) {
            $promoteCount = static::ASSIGNMENT_MIN_REALM_COUNT - $largePacks->count();
            $promotedCount = 0;
            foreach ($smallPacks->sortByDesc('rating') as $idx => $smallPack) {
                $largePacks->put($idx, $smallPack);
                $smallPacks->forget($idx);
                $promotedCount++;
                if ($promotedCount >= $promoteCount) {
                    break;
                }
            }
        }

        // 'Demote' larger packs
        if ($largePacks->count() > static::ASSIGNMENT_MAX_REALM_COUNT) {
            $demoteCount = $largePacks->count() - static::ASSIGNMENT_MAX_REALM_COUNT;
            $demotedCount = 0;
            foreach ($largePacks->sortBy('rating') as $idx => $largePack) {
                $smallPacks->put($idx, $largePack);
                $largePacks->forget($idx);
                $demotedCount++;
                if ($demotedCount >= $demoteCount) {
                    break;
                }
            }
        }

        // Calculate number of realms
        $realmCount = count($largePacks);
        $packsToCreate = max($realmCount - count($smallPacks), 0);
        $soloPlayersToMerge = $packsToCreate * 3;
        $packsToMerge = max(count($smallPacks) - $realmCount, 0);

        // Separate solo players
        $soloPlayers = [];
        foreach ($allPlayers->where('pack_id', null) as $player) {
            if ($player['rating'] == 0) {
                $player['rating'] = $averageRating;
            }
            $soloPlayers[] = $player;
        }
        $soloPlayers = collect($soloPlayers)->keyBy('user_id')->sortBy('rating');

        // Create 'packs' of solo players
        if ($soloPlayersToMerge > 0) {
            $soloPlayersMerged = $soloPlayers->take($soloPlayersToMerge);
            foreach ($soloPlayersMerged->shuffle()->chunk(3) as $soloPack) {
                $smallPacks->push([
                    'size' => 3,
                    'players' => $soloPack->toArray(),
                    'rating' => $this->calculateRating($soloPack->toArray()),
                ]);
            }
            $soloPlayers = $soloPlayers->diffKeys($soloPlayersMerged);
        }

        // Merge smaller packs
        if ($packsToMerge > 0) {
            foreach ($smallPacks->sortBy('size')->take($packsToMerge * 2)->chunk(2) as $idx => $packPair) {
                $firstPack = $packPair->first();
                $lastPack = $packPair->last();
                $players = array_merge($firstPack['players'], $lastPack['players']);
                $combinedPack = [
                    'size' => $firstPack['size'] + $lastPack['size'],
                    'players' => $players,
                    'rating' => $this->calculateRating($players),
                ];
                $smallPacks->put($packPair->keys()[0], $combinedPack);
                $smallPacks->forget($packPair->keys()[1]);
            }
        }

        // Pair packs together into realms
        $realms = [];
        $largePacks = $largePacks->sortByDesc('rating')->values();
        $smallPacks = $smallPacks->sortBy('rating')->values();
        foreach (range(0, $realmCount - 1) as $idx) {
            $players = array_merge($largePacks[$idx]['players'], $smallPacks[$idx]['players']);
            $realms[] = [
                'players' => $players,
                'rating' => $this->calculateRating($players)
            ];
        }

        // Assign solo players to undersized realms
        $medianRealmRating = collect($realms)->median('rating');
        $medianSoloPlayerRating = $soloPlayers->median('rating');
        foreach ($realms as $idx => $realm) {
            while (count($realms[$idx]['players']) < static::MAX_PACKED_PLAYERS_PER_REALM && $soloPlayers->count() > 0) {
                $randomPlayer = null;
                if ($realm['rating'] > $medianRealmRating) {
                    $belowAveragePlayers = $soloPlayers->where('rating', '<', $medianSoloPlayerRating);
                    if ($belowAveragePlayers->count()) {
                        $randomPlayer = $belowAveragePlayers->random();
                    }
                } else {
                    $aboveAveragePlayers = $soloPlayers->where('rating', '>=', $medianSoloPlayerRating);
                    if ($aboveAveragePlayers->count()) {
                        $randomPlayer = $aboveAveragePlayers->random();
                    }
                }
                if ($randomPlayer == null) {
                    $randomPlayer = $soloPlayers->random();
                }
                $realms[$idx]['players'] = array_merge($realms[$idx]['players'], [$randomPlayer]);
                $realms[$idx]['rating'] = $this->calculateRating($realms[$idx]['players']);
                $soloPlayers->forget($randomPlayer['user_id']);
            }
        }

        // Assign solo players evenly to realms
        $realms = array_values(collect($realms)->sortByDesc('rating')->toArray());
        while ($soloPlayers->count() > count($realms)) {
            $current = 0;
            foreach ($soloPlayers->sortBy('rating') as $player) {
                if ($current < count($realms)) {
                    $realms[$current]['players'] = array_merge($realms[$current]['players'], [$player]);
                    $realms[$current]['rating'] = $this->calculateRating($realms[$current]['players']);
                    $soloPlayers->forget($player['user_id']);
                }
                $current++;
            }
        }

        // Assign remaining solo players to lowest realms
        $position = 0;
        $realms = array_values(collect($realms)->sortBy('rating')->toArray());
        foreach ($soloPlayers->sortByDesc('rating') as $player) {
            $realms[$position]['players'] = array_merge($realms[$position]['players'], [$player]);
            $realms[$position]['rating'] = $this->calculateRating($realms[$position]['players']);
            $position++;
        }

        // Create realms and assign dominions
        $realmFactory = app(RealmFactory::class);
        $notificationService = app(NotificationService::class);
        shuffle($realms);
        foreach ($realms as $realmies) {
            $realm = $realmFactory->create($round);
            foreach ($realmies['players'] as $player) {
                $dominion = Dominion::find($player['dominion_id']);
                $dominion->realm_id = $realm->id;
                $dominion->save();
                if ($dominion->pack_id !== null && $dominion->pack->realm_id !== $realm->id) {
                    $dominion->pack->realm_id = $realm->id;
                    $dominion->pack->save();
                }
                // Notifications
                $notificationService->queueNotification('realm_assignment', [
                    '_routeParams' => [$realm->number],
                    'realmNumber' => $realm->number,
                    'discordEnabled' => ($round->discord_guild_id !== null && $round->discord_guild_id !== '')
                ]);
                $notificationService->sendNotifications($dominion, 'irregular_dominion');
            }
            $realm->rating = $this->calculateRating($realmies['players']);
            $realm->save();
        }
    }
}
