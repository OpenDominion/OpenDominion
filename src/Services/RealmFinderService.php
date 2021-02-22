<?php

namespace OpenDominion\Services;

use OpenDominion\Calculators\Dominion\LandCalculator;
use OpenDominion\Factories\DominionFactory;
use OpenDominion\Factories\RealmFactory;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;
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
     * @var int Minimum number of realms to spawn prior to round start
     */
    protected const MIN_REALM_COUNT = 20;

    /**
     * @var int Number of hours after round start to begin realm assignment
     */
    protected const ASSIGNMENT_HOURS_AFTER_START = 24;

    /**
     * @var int Minimum number of realms to create
     */
    protected const ASSIGNMENT_MIN_REALM_COUNT = 20;

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
    public function findRealm(Round $round, Race $race, int $slotsNeeded = 1, bool $forPack = false): ?Realm
    {
        if (now() < $round->start_date || now()->diffInHours($round->start_date) < static::ASSIGNMENT_HOURS_AFTER_START) {
            return $round->realms()->where('number', 0)->first();
        }

        // Get a list of realms which are not full, disregarding pack status for now
        $realmQuery = Realm::active()
            ->with('packs.dominions')
            ->where('round_id', $round->id);

        if (!$round->mixed_alignment) {
            $realmQuery = $realmQuery->where(['realms.alignment' => $race->alignment]);
        }

        $realms = $realmQuery->groupBy('realms.id')
            ->get()
            ->filter(static function ($realm) use ($round, $slotsNeeded, $forPack) {
                // Check pack status
                if ($forPack) {
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

                // Check if realm has enough space
                $availableSlots = ($round->realm_size - $realm->sizeAllocated());
                /** @noinspection IfReturnReturnSimplificationInspection */
                if ($availableSlots < $slotsNeeded) {
                    return false;
                }

                return true;
            });

        if ($realms->count() == 0) {
            return null;
        }

        // Weight the random selection so that smallest realms
        // are chosen twice as often as ones with one additional player
        // and always chosen when all realms have two additional players
        $realmsBySize = $realms->sortBy(function ($realm) {
            return $realm->sizeAllocated();
        });
        $smallestRealmSize = $realmsBySize->first()->sizeAllocated();

        $realmsWeightedBySize = $realms->filter(function ($realm) use ($smallestRealmSize) {
            if ($realm->sizeAllocated() == $smallestRealmSize) {
                return true;
            }
        })->concat($realms->filter(function ($realm) use ($smallestRealmSize) {
            if ($realm->sizeAllocated() == ($smallestRealmSize + 1)) {
                return true;
            }
        }));

        return $realmsWeightedBySize->random();
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
        });
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
        $landCalculator = app(LandCalculator::class);

        // Create a rating for each player based on land size / land conquered
        $ratings = collect(DB::select(DB::raw("SELECT user_id, users.display_name, AVG(IF(stat_total_land_conquered > total_land, total_land, (((stat_total_land_conquered * 2) + (total_land - stat_total_land_conquered)) / 2))) AS rating FROM (SELECT user_id, stat_total_land_conquered, (land_plain+land_mountain+land_swamp+land_cavern+land_forest+land_hill+land_water) AS total_land, @land_rank := IF(@current_user = user_id, @land_rank + 1, 1) AS ranking, @current_user := user_id FROM dominions WHERE round_id IN (18, 19, 20, 22, 24, 26, 28) HAVING total_land > 700 ORDER BY user_id, total_land DESC) stats JOIN users ON users.id = user_id WHERE ranking <= 3 GROUP BY user_id ORDER BY rating;")))->keyBy('user_id');

        // Fetch all registered dominions
        $registeredDominions = $round->activeDominions()->get();

        // Collect data for all dominions
        $allPlayers = collect();
        foreach ($registeredDominions as $dominion) {
            $allPlayers = $allPlayers->push([
                'dominion_id' => $dominion->id,
                'pack_id' => $dominion->pack_id,
                'user_id' => $dominion->user_id,
                'rating' => isset($ratings[$dominion->user_id]) ? $ratings[$dominion->user_id]->rating : 0
            ]);
        }

        // Calculations to be used later
        $averageRating = $allPlayers->where('rating', '!=', 0)->median('rating');

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

        // Merge 2-packs into 4-packs
        $packsMerged = [];
        $largePackCount = 0;
        $smallPackCount = 0;
        foreach ($packs as $packId => $pack) {
            // Close open packs and calculate realm count
            Pack::find($packId)->close();
            if ($pack['size'] > 2) {
                $largePackCount++;
                $packsMerged[$packId] = $pack;
            } else {
                $smallPackCount++;
            }
        }
        $realmCount = max(ceil(($largePackCount / 2) + ($smallPackCount / 4)), static::ASSIGNMENT_MIN_REALM_COUNT);
        $packsToMerge = $largePackCount + $smallPackCount - (2 * $realmCount);
        $currentPackSize = 0;
        foreach (collect($packs)->where('size', '<=', 2)->shuffle() as $packId => $pack) {
            if ($packsToMerge > 0) {
                if ($currentPackSize == 0) {
                    $currentPack = $packId;
                    $packsMerged[$packId] = $pack;
                } else {
                    $packsMerged[$currentPack]['players'] = array_merge($packsMerged[$currentPack]['players'], $pack['players']);
                }
                $packsMerged[$currentPack]['rating'] = $this->calculateRating($packsMerged[$currentPack]['players']);
                $currentPackSize += $pack['size'];
                if ($currentPackSize > 2) {
                    $currentPackSize = 0;
                    $packsToMerge--;
                }
            } else {
                $packsMerged[$packId] = $pack;
            }
        }

        // Randomize in chunks
        $packsByRating = array_values(collect($packsMerged)->sortByDesc('rating')->toArray());
        $packsChunked = array_chunk($packsByRating, 4);
        $packsByRating = array();
        foreach ($packsChunked as $chunk) {
            shuffle($chunk);
            $packsByRating = array_merge($packsByRating, $chunk);
        }

        // Pair packs together into realms
        $realms = [];
        $keysAssigned = [];
        $midpoint = (int)ceil(count($packsByRating)/2);
        foreach (range(0, $midpoint - 1) as $key) {
            $matchKey = count($packsByRating) - 1 - $key;
            if (!in_array($matchKey, $realms)) {
                if ($key != $midpoint - 1 || !(count($packsByRating) % 2)) {
                    $players = array_merge($packsByRating[$key]['players'], $packsByRating[$matchKey]['players']);
                }
            } else {
                $players = $packsByRating[$key]['players'];
            }
            $realms[] = [
                'players' => $players,
                'rating' => $this->calculateRating($players)
            ];
        }

        // Separate solo players
        $soloPlayers = [];
        foreach ($allPlayers->where('pack_id', null) as $player) {
            if ($player['rating'] == 0) {
                $player['rating'] = $averageRating;
            }
            $soloPlayers[] = $player;
        }
        $soloPlayers = collect($soloPlayers)->keyBy('user_id')->sortBy('rating');

        // Assign solo players to undersized realms
        $maxRating = collect($realms)->max('rating');
        foreach ($realms as $key => $realm) {
            if ($realm['rating'] > (($maxRating - $averageRating) / 2)) {
                $targetRating = ($maxRating - $averageRating) / 2;
            } else {
                $targetRating = $averageRating;
            }
            $attempts = 0;
            while (count($realms[$key]['players']) < 8 && $attempts < 72) {
                $randomPlayer = $soloPlayers->random();
                if (($realm['rating'] > $targetRating && $randomPlayer['rating'] < $targetRating) || ($realm['rating'] < $targetRating && $randomPlayer['rating'] > $targetRating) || $attempts > 48) {
                    $realms[$key]['players'] = array_merge($realms[$key]['players'], [$randomPlayer]);
                    $realms[$key]['rating'] = $this->calculateRating($realms[$key]['players']);
                    $soloPlayers->forget($randomPlayer['user_id']);
                }
                $attempts++;
            }
        }

        // Assign solo players evenly to realms
        $realms = array_values(collect($realms)->sortByDesc('rating')->toArray());
        while ($soloPlayers->count() > count($realms)) {
            $current = 0;
            foreach ($soloPlayers->sortBy('rating') as $player) {
                if ($current < $midpoint) {
                    $realms[$current]['players'] = array_merge($realms[$current]['players'], [$player]);
                    $realms[$current]['rating'] = $this->calculateRating($realms[$current]['players']);
                    $soloPlayers->forget($player['user_id']);
                }
                $current++;
            }

            $current = count($realms) - 1;
            foreach ($soloPlayers->sortByDesc('rating') as $player) {
                if ($current >= $midpoint) {
                    $realms[$current]['players'] = array_merge($realms[$current]['players'], [$player]);
                    $realms[$current]['rating'] = $this->calculateRating($realms[$current]['players']);
                    $soloPlayers->forget($player['user_id']);
                }
                $current--;
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
        $notificationService = app(NotificationService::class);
        foreach (shuffle($realms) as $realmies) {
            $realm = RealmFactory::create($round);
            foreach ($realmies['players'] as $player) {
                $dominion = Dominion::find($player['dominion_id']);
                $dominion->realm_id = $realm->id;
                $dominion->save();
                if ($dominion->pack_id !== null && $pack->realm_id !== $realm->id) {
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
        }
    }
}
