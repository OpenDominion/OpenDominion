<?php

namespace OpenDominion\Services;

use Auth;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;
use RuntimeException;

class PackService
{
    public function getOrCreatePack(
        Round $round,
        Race $race,
        string $packName,
        string $packPassword,
        int $packSize,
        bool $createPack
    ): ?Pack {
        return (
            $createPack
                ? $this->createPack($round, $packName, $packPassword, $packSize)
                : $this->getPack($round, $race, $packName, $packPassword)
        );
    }

    protected function createPack(Round $round, string $packName, string $packPassword, int $packSize): Pack
    {
        if (($packSize < 2) || ($packSize > $round->pack_size)) {
            throw new RuntimeException("Pack size must be between 2 and {$round->pack_size}.");
        }

        return Pack::create([
            'round_id' => $round->id,
            'user_id' => Auth::user()->id,
            'name' => $packName,
            'password' => $packPassword,
            'size' => $packSize,
        ]);
    }

    protected function getPack(Round $round, Race $race, string $packName, string $packPassword): ?Pack
    {
        $pack = Pack::where([
            'round_id' => $round->id,
            'name' => $packName,
            'password' => $packPassword,
        ])->withCount('dominions')->first();

        if (!$pack) {
            return null;
        }

        if ($pack->dominions_count >= $pack->size) {
            throw new RuntimeException('Pack is already full');
        }

        if ($pack->realm->alignment !== $race->alignment) {
            throw new RuntimeException('Race has wrong alignment to the rest of pack.');
        }

        return $pack;
    }
}
