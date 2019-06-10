<?php

namespace OpenDominion\Services;

use OpenDominion\Exceptions\GameException;
use OpenDominion\Models\Dominion;
use OpenDominion\Models\Pack;
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
     * @param string $alignment
     * @param string $packName
     * @param string $packPassword
     * @return Pack|null
     * @throws GameException
     */
    public function getPack(Round $round, string $alignment, string $packName, string $packPassword): ?Pack
    {
        $pack = Pack::where([
            'round_id' => $round->id,
            'name' => $packName,
            'password' => $packPassword,
        ])->withCount('dominions')->first();

        if (!$pack) {
            throw new GameException('Pack with specified name/password was not found.');
        }

        if ($pack->dominions_count >= $pack->size) {
            throw new GameException('Pack is already full.');
        }

        if ($pack->realm->alignment !== $alignment) {
            throw new GameException("Selected race has wrong alignment to the rest of pack. Pack requires a {$pack->realm->alignment} aligned race.");
        }

        return $pack;
    }
}
