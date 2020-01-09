<?php

namespace OpenDominion\Services;

use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFinderService
{
    /**
     * @var int Maximum number of packs that can exist in a single realm
     */
    protected const MAX_PACKS_PER_REALM = 2;

    /**
     * @var int Coefficent for maximum number of players allowed in packs after the first
     */
    protected const MAX_PACK_SIZE_AFTER_FIRST = 0.5;

    /**
     * Finds and returns the first best realm for a new Dominion to settle in.
     *
     * The number of dominions that can exist in a realm is dictated by
     * $round->realm_size.
     *
     * @param Round $round
     * @param Race $race
     * @param int $slotsNeeded
     * @param bool $forPack
     *
     * @return Realm|null
     * @see DominionFactory::create()
     */
    public function findRandomRealm(Round $round, Race $race, int $slotsNeeded = 1, bool $forPack = false): ?Realm
    {
        // Get a list of realms which are not full, disregarding pack status for now
        $realmQuery = Realm::query()
            ->with('packs.dominions')
            ->withCount('dominions')
            ->where([
                'realms.round_id' => $round->id
            ]);

        if (!$round->mixed_alignment) {
            $realmQuery = $realmQuery->where(['realms.alignment' => $race->alignment]);
        }

        $realms = $realmQuery->groupBy('realms.id')
            ->having('dominions_count', '<', $round->realm_size)
            ->get()
            ->filter(static function ($realm) use ($round, $slotsNeeded, $forPack) {
                // Check pack status
                if ($forPack) {
                    if (static::MAX_PACKS_PER_REALM !== null) {
                        // Reached maximum number of packs
                        if ($realm->packs->count() >= static::MAX_PACKS_PER_REALM) {
                            return false;
                        }
                        // Check if new pack and an existing pack are both larger than
                        $additionalPackMax = static::MAX_PACK_SIZE_AFTER_FIRST * $round->pack_size;
                        if ($slotsNeeded > $additionalPackMax) {
                            foreach ($realm->packs as $pack) {
                                if ($pack->isClosed()) {
                                    if ($pack->dominions->count() > $additionalPackMax) {
                                        return false;
                                    }
                                } else {
                                    if ($pack->size > $additionalPackMax) {
                                        return false;
                                    }
                                }
                            }
                        }
                    }
                }

                $availableSlots = ($round->realm_size - $realm->dominions_count);
                foreach ($realm->packs as $pack) {
                    if ($pack->isClosed()) {
                        continue;
                    }

                    $availableSlots -= ($pack->size - $pack->dominions->count());
                }

                /** @noinspection IfReturnReturnSimplificationInspection */
                if ($availableSlots < $slotsNeeded) {
                    return false;
                }

                return true;
            })
            ->sortBy('dominions_count');

        if ($realms->count() == 0) {
            return null;
        }

        // Weight the random selection so that smallest realms
        // are chosen twice as often as ones with one additional player
        // and always chosen when all realms have two additional players
        $smallestRealmSize = (int)$realms->min('dominions_count');
        $realmsWeightedBySize = $realms->where('dominions_count', '=', $smallestRealmSize);
        $realmsWeightedBySize = $realmsWeightedBySize->concat($realms->where('dominions_count', '<=', $smallestRealmSize + 1));

        return $realmsWeightedBySize->random();
    }
}
