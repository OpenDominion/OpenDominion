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
        bool $createPack): ?Pack
    {
        $packNameIsNullOrEmpty = (!isset($packName) || trim($packName) === '');
        $packPasswordIsNullOrEmpty = (!isset($packPassword) || trim($packPassword) === '');
        if($packNameIsNullOrEmpty || $packPasswordIsNullOrEmpty) {
            throw new RuntimeException('You need to enter both name and password for the pack.');
        }

        $pack = null;
        if($createPack) {
            if($packSize < 2 || $packSize > $round->pack_size)
            {
                throw new RuntimeException('Pack size must be between 2 and 6.');
            }

            $pack = Pack::create([
                'round_id' => $round->id,
                'user_id' => Auth::user()->id,
                'name' => $packName,
                'password' => $packPassword,
                'size' => $packSize
            ]);

            $packId = $pack->id;

            $pack = Pack::lockForUpdate()->findOrFail($packId);
        }
        else {
            $packs = Pack::where([
                'name' => $packName,
                'password' => $packPassword,
                'round_id' => $round->id
            ])->withCount('dominions')->lockForUpdate()->get();

            if($packs->isEmpty()) {
                throw new RuntimeException('No pack with that password found in round {$round->number}');
            }

            $pack = $packs->first();

            // TODO: race condition here
            // TODO: Pack size should be a setting?
            if($pack->dominions_count >= $pack->size) {
                throw new RuntimeException('Pack is already full');
            }

            if($pack->realm->alignment !== $race->alignment){
                throw new RuntimeException('Race has wrong aligment to the rest of pack.');
            }
        }

        return $pack;
    }
}
