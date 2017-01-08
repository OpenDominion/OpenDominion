<?php

namespace OpenDominion\Http\Controllers;

use OpenDominion\Models\Dominion;

class DominionController extends AbstractController
{
    public function postPlay(Dominion $dominion)
    {
        // assert that dominion->round is active
        // assert dominion hasnt been banned?

        session(['dominion_id' => $dominion->id]);
        return redirect(route('dominion.status'));
    }

    public function getStatus(Dominion $dominion)
    {
        return view('pages.dominion.status', [
            'dominion' => $dominion,
        ]);
    }
}
