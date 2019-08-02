<?php

namespace OpenDominion\Services;

use OpenDominion\Factories\DominionFactory;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Realm;
use OpenDominion\Models\Round;

class RealmFinderService
{
    /** @var int Maximum number of packs that can exist in a single realm */
    public $maxPacksPerRealm = 1;

    /**
     * Finds and returns the first best realm for a new Dominion to settle in.
     *
     * The number of dominions that can exist in a realm is dictated by
     * $round->realm_size.
     *
     * @see DominionFactory::create()
     *
     * @param Round $round
     * @param Race $race
     * @param int $slotsNeeded
     * @param bool $forPack
     *
     * @return Realm|null
     */
    public function findRandomRealm(Round $round, Race $race, int $slotsNeeded = 1, bool $forPack = false): ?Realm
    {
        // Get a list of realms which are not full, disregarding pack status for now
        $realms = Realm::query()
            ->with('packs.dominions') // todo: can probably be just with('packs')
            ->withCount('dominions')
            ->where([
                'realms.round_id' => $round->id,
                'realms.alignment' => $race->alignment,
            ])
            ->groupBy('realms.id')
            ->having('dominions_count', '<', $round->realm_size)
            ->inRandomOrder()// Could be refactored later to sort on dominions_count asc, to fill more empty realms first
            ->get();

        // Iterate over suspected eligible realms and check pack status
        foreach ($realms as $realm) {
            if ($forPack && ($this->maxPacksPerRealm !== null) && ($realm->packs->count() >= $this->maxPacksPerRealm)) {
                continue;
            }

            $availableSlots = ($round->realm_size - $realm->dominions_count);

            foreach ($realm->packs as $pack) {
                if ($pack->isClosed()) {
                    continue;
                }

                $availableSlots -= ($pack->size - $pack->dominions->count());
            }

            if ($availableSlots >= $slotsNeeded) {
                return Realm::find($realm->id); // return fresh copy
            }
        }

        return null;
    }

    public function findRandomRealmForPack(Round $round, Race $race, Pack $pack): ?Realm
    {
        // todo: move this to dominionfactory instead
        $realm = $this->findRandomRealm($round, $race, $pack->size, true);

        if ($realm !== null) {
            $pack->realm_id = $realm->id;
            $pack->save();

//            $realm->has_pack = true;
//            $realm->reserved_slots = $round->pack_size;
//            $realm->save();

//            $pack->load('realm');
        }

        return $realm;
    }
}
