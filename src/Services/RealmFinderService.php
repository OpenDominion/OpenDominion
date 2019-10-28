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
    protected const MAX_PACKS_PER_REALM = 1;

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
                if ($forPack && (static::MAX_PACKS_PER_REALM !== null) && ($realm->packs->count() >= static::MAX_PACKS_PER_REALM)) {
                    return false;
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

        $smallestRealmSize = (int)$realms->min('dominions_count');
        $largestRealmSize = (int)$realms->max('dominions_count');
        $realmSizeRange = $largestRealmSize - $smallestRealmSize;

        // Weight the random selection
        // for every possible realm size between smallest and largest, duplicate smaller realms
        $realmsWeightedBySize = $realms->where('dominions_count', '=', $smallestRealmSize);
        for ($i=1; $i<=$realmSizeRange; $i++) {
            $realmsWeightedBySize = $realmsWeightedBySize->concat($realms->where('dominions_count', '<=', $smallestRealmSize + $i));
        }
        return $realmsWeightedBySize->random();
    }
}
