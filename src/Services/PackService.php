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
     */
    public function getPack(Round $round, string $packName, string $packPassword, Race $race): Pack
    {
        $pack = Pack::where([
            'round_id' => $round->id,
            'name' => $packName,
            'password' => $packPassword,
        ])->withCount([
            'dominions',
            'dominions.race_id as players_with_race' => function (Builder $query) use ($race) {
                $query->where('race_id', $race->id);
            }
            ])->first();

        if (!$pack) {
            throw new GameException('Pack with specified name/password was not found.');
        }

        if ($pack->dominions_count >= $pack->size) {
            throw new GameException('Pack is already full.');
        }

        if($round->players_per_race != 0 && $pack->players_with_race >= $round->players_per_race) {
            throw new GameException('Selected race has already been selected by the maximum amount of players.');
        }

        if (!$round->mixed_alignments && $pack->realm->alignment !== $race->alignment) {
            throw new GameException(sprintf(
                'Selected race has wrong alignment to the rest of pack. Pack requires %s %s aligned race.',
                (($pack->realm->alignment === 'evil') ? 'an' : 'a'),
                $pack->realm->alignment
            ));
        }

        return $pack;
    }
}
