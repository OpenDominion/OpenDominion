<?php

namespace OpenDominion\Services;

use Auth;
use Illuminate\Http\Request;
use OpenDominion\Models\Pack;
use OpenDominion\Models\Race;
use OpenDominion\Models\Round;

class PackService
{
    public function getOrCreatePack(Request $request, Round $round, Race $race): Pack
    {
        // TODO: Handle validation errors gracefully...
        if(!$request->filled('pack_password')) {
            return null;
        }

        $password = $request->get('pack_password');
        $name = $request->get('pack_name');
        $pack = null;
        if($request->has('create_pack')) {
            $packSize = $request->get('pack_size');

            if($packSize < 2 || $packSize > 6)
            {
                throw new RuntimeException("Pack size must be between 2 and 6.");
            }

            $pack = Pack::create([
                'round_id' => $round->id,
                'user_id' => Auth::user()->id,
                'name' => $name,
                'password' => $password,
                'size' => $packSize
            ]);

            $packId = $pack->id;

            $pack = Pack::lockForUpdate()->find($packId);
        }
        else {
            $packs = Pack::where([
                'name' => $name,
                'password' => $password,
                'round_id' => $round->id
            ])->withCount('dominions')->lockForUpdate()->get();
    
            if($packs->isEmpty()) {
                throw new RuntimeException("No pack with that password found in round {$round->number}");
            }

            $pack = $packs->first();

            // TODO: race condition here
            // TODO: Pack size should be a setting?
            if($pack->dominions_count == 6) {
                throw new RuntimeException("Pack is already full");
            }

            if($pack->realm->alignment !== $race->alignment){
                throw new RuntimeException("Race has wrong aligment to the rest of pack.");
            }
        }

        return $pack;
    }
}