<?php

namespace OpenDominion\Services;

class PackService
{
    public function getOrCreatePack(Request $request, Round $round, Race $race): Pack
    {
        // TODO: Handle validation errors gracefully...
        if(!$request->filled('pack_password')) {
            return null;
        }

        $password = $request->get('pack_password');

        if($request->has('create_pack'))
        {
            $packSize = $request->get('pack_size');

            if($packSize < 2 || $packSize > 6)
            {
                throw new RuntimeException("Pack size must be between 2 and 6.");
            }

            $pack = Pack::create([
                'round_id' => $round->id,
                'user_id' => Auth::user()->id,
                'password' => $password,
                'size' => $packSize
            ]);
        }
        else {
            $pack = Pack::where([
                'password' => $password,
                'round_id' => $round->id
            ])->withCount('dominions')->findOrFail();
    
            // TODO: race condition here
            // TODO: Pack size should be a setting?
            if($pack->dominions_count == 6) {
                throw new RuntimeException("Pack is already full");
            }

            if($pack->realm->alignment !== $race->alignment){
                throw new RuntimeException("Race has wrong aligment to rest of pack.");
            }
        }

        return $pack;
    }
}