<?php

namespace OpenDominion\Services;

use Illuminate\Database\Eloquent\Builder;
use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;

class PackService
{
    /**
     * Creates a new pack for a Dominion.
     *
     * @param Dominion $dominion
     * @param string $packName
     * @param string $packPassword
     * @param int $packSize
     * @return Pack
     * @throws GameException
     */
    public function createPack(Dominion $dominion, string $packName, string $packPassword, int $packSize): Pack
    {
        if (($packSize < 2) || ($packSize > $dominion->round->pack_size)) {
            throw new GameException("Pack size must be between 2 and {$dominion->round->pack_size}.");
        }

        // todo: check if pack already exists with same name and password, and
        // throw exception if that's the case

        return Pack::create([
            'round_id' => $dominion->round->id,
            'realm_id' => $dominion->realm->id,
            'creator_dominion_id' => $dominion->id,
            'name' => $packName,
            'password' => $packPassword,
            'size' => $packSize,
        ]);

        // todo: set $dominion->pack_id = $pack->id here?
    }

    /**
     * Gets a pack based on pack based on round, alignment, pack name and password.
     *
     * @param Round $round
     * @param string $packName
     * @param string $packPassword
     * @param Race $race
     * @return Pack
     * @throws GameException
     */
    public function getPack(Round $round, string $packName, string $packPassword, Race $race): Pack
    {
        $pack = Pack::where([
            'round_id' => $round->id,
            'name' => $packName,
            'password' => $packPassword,
        ])->first();

        if (!$pack) {
            throw new GameException('Pack with specified name/password was not found.');
        }

        if ($pack->remainingSlots() <= 0) {
            throw new GameException('Pack is already full.');
        }

        if (!$this->checkRaceLimitForPack($pack, $race)) {
            throw new GameException('Selected race has already been chosen by the maximum number of players.');
        }

        if (!$round->mixed_alignment && ($pack->realm->alignment !== $race->alignment)) {
            throw new GameException(sprintf(
                'Selected race has wrong alignment to the rest of pack. Pack requires %s %s aligned race.',
                (($pack->realm->alignment === 'evil') ? 'an' : 'a'),
                $pack->realm->alignment
            ));
        }

        return $pack;
    }

    public function checkRaceLimitForPack(Pack $pack, Race $race): bool
    {
        $otherRaceId = null;
        $playersPerRace = (int)$pack->round->players_per_race;
        if ($pack->size >= $pack->round->pack_size) {
            // Packs of maximum size must use unique races
            $playersPerRace = 1;
        }

        if ($playersPerRace !== 0) {
            if ($race->name === 'Spirit') {
                // Count Undead with Spirit
                $otherRaceId = Race::where('name', 'Undead')->firstOrFail()->id;
            } elseif ($race->name === 'Undead') {
                // Count Spirit with Undead
                $otherRaceId = Race::where('name', 'Spirit')->firstOrFail()->id;
            } elseif ($race->name === 'Nomad') {
                // Count Human with Nomad
                $otherRaceId = Race::where('name', 'Human')->firstOrFail()->id;
            } elseif ($race->name === 'Human') {
                // Count Nomad with Human
                $otherRaceId = Race::where('name', 'Nomad')->firstOrFail()->id;
            }
        }

        $pack = Pack::where('id', $pack->id)->withCount([
            'dominions AS players_with_race' => static function (Builder $query) use ($race, $otherRaceId) {
                $query->where('race_id', $race->id);

                if ($otherRaceId) {
                    $query->orWhere('race_id', $otherRaceId);
                }
            }
        ])->first();

        if (($playersPerRace !== 0) && ($pack->players_with_race >= $playersPerRace)) {
            return false;
        }

        return true;
    }

    public function checkRaceLimitForRealm(Realm $realm, Race $race): bool
    {
        $packs = $realm->packs()->dominions()->sum('players_with_race');

        $packCount = $realm->packs()->count();
        if ($packCount > 1) {
            $playersWithRace = $realm->packs->sum(
                function ($pack) use ($race) {
                    return $pack->dominions->where('race_id', $race->id)->count();
                }
            );
            if ($playersWithRace >= 3) {
                // Packs in the same realm cannot restart for more than 3 of single race
                return false;
            }
        }

        return true;
    }
}
