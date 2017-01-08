<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Dominion;

class DominionController extends AbstractController
{
    public function postPlay(Dominion $dominion)
    {
        // set session
        // redirect to /status
        dd($dominion);
    }

    public function getStatus(Dominion $dominion)
    {
        return view('pages.dominion.status', [
            'dominion' => $dominion,
        ]);
    }
}
